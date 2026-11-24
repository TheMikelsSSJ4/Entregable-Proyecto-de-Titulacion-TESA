<?php

namespace App\Services;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoFactura;
use App\Enums\MetodoPago;
use App\Enums\TipoTransaccion;
use App\Exceptions\OperacionNoPermitidaException;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Emisión de facturas (contado / crédito) y cotizaciones maestro-detalle (RF-02, RF-03).
 *
 * Precio unitario, % de descuento y % de IVA de cada línea salen del catálogo salvo
 * que se ajusten a mano en el documento; los montos (subtotal, IVA, total) siempre
 * los calcula el servidor, nunca se aceptan desde el navegador.
 */
class FacturacionService
{
    public function __construct(
        private readonly CalculadoraComercial $calculadora,
        private readonly CatalogoService $catalogo,
    ) {}

    /**
     * Siguiente número de factura de la serie configurada (001-001-000000001).
     */
    public function sugerirNumeroFactura(): string
    {
        $serie = config('contable.establecimiento').'-'.config('contable.punto_emision').'-';

        $ultimo = Factura::query()
            ->where('numero_factura', 'like', $serie.'%')
            ->max('numero_factura');

        $secuencial = $ultimo ? ((int) substr($ultimo, -9)) + 1 : 1;

        return $serie.str_pad((string) $secuencial, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Registra una venta con sus líneas. Si es al contado se genera el pago por
     * el total y la factura queda "pagada"; si es a crédito queda "pendiente".
     *
     * @param  array{
     *     cliente_id: int|string,
     *     numero_factura: string,
     *     tipo_transaccion: string,
     *     fecha_emision: string,
     *     fecha_vencimiento?: string|null,
     *     vendedor_id?: int|string|null,
     *     lineas: list<array{producto_id: int|string, cantidad: string|float, precio_unitario?: string|null, porcentaje_descuento?: string|null, porcentaje_iva?: string|null, actualizar_catalogo?: bool}>,
     *     metodo_pago?: string|null,
     *     comprobante_referencia?: string|null,
     *     cotizacion_id?: int|string|null,
     * }  $datos
     */
    public function registrarFactura(array $datos, Usuario $usuario): Factura
    {
        $tipo = TipoTransaccion::from($datos['tipo_transaccion']);
        $lineas = $this->construirLineas($datos['lineas']);
        $totales = $this->calculadora->totalizar($lineas);
        $fechaEmision = Carbon::parse($datos['fecha_emision'])->startOfDay();
        $fechaVencimiento = $tipo === TipoTransaccion::Credito
            ? Carbon::parse($datos['fecha_vencimiento'])->startOfDay()
            : $fechaEmision->copy();
        $cotizacionId = ! empty($datos['cotizacion_id']) ? (int) $datos['cotizacion_id'] : null;

        try {
            return DB::transaction(function () use ($datos, $usuario, $tipo, $lineas, $totales, $fechaEmision, $fechaVencimiento, $cotizacionId): Factura {
                if ($cotizacionId !== null) {
                    $this->marcarCotizacionFacturada($cotizacionId, (int) $datos['cliente_id']);
                }

                $factura = Factura::create([
                    'numero_factura' => $datos['numero_factura'],
                    'cliente_id' => (int) $datos['cliente_id'],
                    'usuario_id' => $usuario->id,
                    'vendedor_id' => (int) ($datos['vendedor_id'] ?? $usuario->id),
                    'cotizacion_id' => $cotizacionId,
                    'tipo_transaccion' => $tipo,
                    'fecha_emision' => $fechaEmision,
                    'fecha_vencimiento' => $fechaVencimiento,
                    ...$totales,
                    'estado' => EstadoFactura::Pendiente,
                ]);

                $factura->detalles()->createMany($lineas);
                $this->actualizarCatalogo($datos['lineas']);

                if ($tipo === TipoTransaccion::Contado) {
                    $factura->pagos()->create([
                        'usuario_id' => $usuario->id,
                        'fecha_pago' => $fechaEmision,
                        'monto_abonado' => $totales['total'],
                        'metodo_pago' => $datos['metodo_pago'] ?? MetodoPago::Efectivo->value,
                        'comprobante_referencia' => $datos['comprobante_referencia'] ?? null,
                    ]);

                    $factura->update(['estado' => EstadoFactura::Pagada]);
                }

                return $factura;
            });
        } catch (UniqueConstraintViolationException) {
            throw new OperacionNoPermitidaException(
                "El número de factura {$datos['numero_factura']} ya fue registrado por otro usuario. Use el siguiente secuencial."
            );
        }
    }

    /**
     * Anula una factura sin abonos. Si tiene pagos, primero deben revertirse
     * (sólo Gerencia) para conservar la trazabilidad de la cartera.
     */
    public function anularFactura(Factura $factura): Factura
    {
        return DB::transaction(function () use ($factura): Factura {
            $factura = Factura::query()->lockForUpdate()->findOrFail($factura->id);

            if ($factura->estado === EstadoFactura::Anulada) {
                throw new OperacionNoPermitidaException("La factura {$factura->numero_factura} ya está anulada.");
            }

            if ($factura->pagos()->exists()) {
                throw new OperacionNoPermitidaException(
                    "La factura {$factura->numero_factura} tiene pagos registrados. Revierta los abonos antes de anularla."
                );
            }

            $factura->update(['estado' => EstadoFactura::Anulada]);

            return $factura;
        });
    }

    /**
     * @param  array{cliente_id: int|string, vendedor_id?: int|string|null, fecha_emision: string, lineas: list<array{producto_id: int|string, cantidad: string|float, precio_unitario?: string|null, porcentaje_descuento?: string|null, porcentaje_iva?: string|null, actualizar_catalogo?: bool}>}  $datos
     */
    public function registrarCotizacion(array $datos, Usuario $usuario): Cotizacion
    {
        $lineas = $this->construirLineas($datos['lineas']);

        return DB::transaction(function () use ($datos, $usuario, $lineas): Cotizacion {
            $cotizacion = Cotizacion::create([
                'cliente_id' => (int) $datos['cliente_id'],
                'usuario_id' => $usuario->id,
                'vendedor_id' => (int) ($datos['vendedor_id'] ?? $usuario->id),
                'fecha_emision' => $datos['fecha_emision'],
                ...$this->calculadora->totalizar($lineas),
                'estado' => EstadoCotizacion::Pendiente,
            ]);

            $cotizacion->detalles()->createMany($lineas);
            $this->actualizarCatalogo($datos['lineas']);

            return $cotizacion;
        });
    }

    /**
     * Reemplaza las líneas de la cotización y recalcula sus totales.
     *
     * @param  array{cliente_id: int|string, vendedor_id?: int|string|null, fecha_emision: string, estado: string, lineas: list<array{producto_id: int|string, cantidad: string|float, precio_unitario?: string|null, porcentaje_descuento?: string|null, porcentaje_iva?: string|null, actualizar_catalogo?: bool}>}  $datos
     */
    public function actualizarCotizacion(Cotizacion $cotizacion, array $datos): Cotizacion
    {
        if ($cotizacion->estado === EstadoCotizacion::Facturada) {
            throw new OperacionNoPermitidaException("La cotización {$cotizacion->numero} ya fue facturada y no puede modificarse.");
        }

        $lineas = $this->construirLineas($datos['lineas']);

        return DB::transaction(function () use ($cotizacion, $datos, $lineas): Cotizacion {
            $cotizacion->detalles()->delete();

            $cotizacion->update([
                'cliente_id' => (int) $datos['cliente_id'],
                'vendedor_id' => (int) ($datos['vendedor_id'] ?? $cotizacion->vendedor_id),
                'fecha_emision' => $datos['fecha_emision'],
                ...$this->calculadora->totalizar($lineas),
                'estado' => EstadoCotizacion::from($datos['estado']),
            ]);

            $cotizacion->detalles()->createMany($lineas);
            $this->actualizarCatalogo($datos['lineas']);

            return $cotizacion;
        });
    }

    /**
     * Líneas sugeridas para facturar una cotización (sólo productos todavía activos).
     *
     * @return list<array{producto_id: int, cantidad: string, precio_unitario: string, porcentaje_descuento: string, porcentaje_iva: string}>
     */
    public function lineasDesdeCotizacion(Cotizacion $cotizacion): array
    {
        return $cotizacion->detalles()
            ->whereHas('producto', fn ($q) => $q->where('activo', true))
            ->get(['producto_id', 'cantidad', 'precio_unitario', 'porcentaje_descuento', 'porcentaje_iva'])
            ->map(fn ($detalle): array => [
                'producto_id' => $detalle->producto_id,
                'cantidad' => (string) $detalle->cantidad,
                'precio_unitario' => (string) $detalle->precio_unitario,
                'porcentaje_descuento' => (string) $detalle->porcentaje_descuento,
                'porcentaje_iva' => (string) (int) $detalle->porcentaje_iva,
            ])
            ->values()
            ->all();
    }

    /**
     * Ventas del mes en curso visibles para el usuario (excluye anuladas).
     *
     * @return array{total: string, cantidad: int, contado: string, credito: string}
     */
    public function resumenVentasMes(Usuario $usuario): array
    {
        $fila = Factura::query()
            ->visiblesPara($usuario)
            ->where('estado', '!=', EstadoFactura::Anulada->value)
            ->whereBetween('fecha_emision', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('COUNT(*) AS cantidad')
            ->selectRaw('COALESCE(SUM(total), 0) AS total')
            ->selectRaw("COALESCE(SUM(total) FILTER (WHERE tipo_transaccion = 'contado'), 0) AS contado")
            ->selectRaw("COALESCE(SUM(total) FILTER (WHERE tipo_transaccion = 'credito'), 0) AS credito")
            ->toBase()
            ->first();

        return [
            'total' => bcadd((string) $fila->total, '0', 2),
            'cantidad' => (int) $fila->cantidad,
            'contado' => bcadd((string) $fila->contado, '0', 2),
            'credito' => bcadd((string) $fila->credito, '0', 2),
        ];
    }

    /**
     * Historial unificado de facturas y cotizaciones (Módulo de Documentos).
     *
     * @param  array{tipo?: string|null, buscar?: string|null}  $filtros
     */
    public function historialDocumentos(Usuario $usuario, array $filtros, int $filasPorPagina = 10): LengthAwarePaginator
    {
        $tipo = $filtros['tipo'] ?? null;
        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        $facturas = DB::table('facturas')
            ->join('clientes', 'clientes.id', '=', 'facturas.cliente_id')
            ->selectRaw("'factura' AS tipo_documento, facturas.id, facturas.numero_factura AS numero, clientes.razon_social AS cliente, facturas.fecha_emision AS fecha, facturas.total, facturas.estado, facturas.tipo_transaccion, facturas.created_at")
            ->when(! $usuario->accedeAFinanzas(), fn (QueryBuilder $q) => $q->where(fn (QueryBuilder $w) => $w
                ->where('facturas.vendedor_id', $usuario->id)
                ->orWhere('facturas.usuario_id', $usuario->id)))
            ->when($buscar !== '', fn (QueryBuilder $q) => $q->where(fn (QueryBuilder $w) => $w
                ->whereLike('facturas.numero_factura', "%{$buscar}%")
                ->orWhereLike('clientes.razon_social', "%{$buscar}%")));

        $cotizaciones = DB::table('cotizaciones')
            ->join('clientes', 'clientes.id', '=', 'cotizaciones.cliente_id')
            ->selectRaw("'cotizacion' AS tipo_documento, cotizaciones.id, CONCAT('COT-', LPAD(cotizaciones.id::text, 6, '0')) AS numero, clientes.razon_social AS cliente, cotizaciones.fecha_emision AS fecha, cotizaciones.total, cotizaciones.estado, NULL AS tipo_transaccion, cotizaciones.created_at")
            ->when(! $usuario->accedeAFinanzas(), fn (QueryBuilder $q) => $q->where(fn (QueryBuilder $w) => $w
                ->where('cotizaciones.vendedor_id', $usuario->id)
                ->orWhere('cotizaciones.usuario_id', $usuario->id)))
            ->when($buscar !== '', fn (QueryBuilder $q) => $q->where(fn (QueryBuilder $w) => $w
                ->whereLike(DB::raw("CONCAT('COT-', LPAD(cotizaciones.id::text, 6, '0'))"), "%{$buscar}%")
                ->orWhereLike('clientes.razon_social', "%{$buscar}%")));

        $union = match ($tipo) {
            'facturas' => $facturas,
            'cotizaciones' => $cotizaciones,
            default => $facturas->unionAll($cotizaciones),
        };

        return DB::query()
            ->fromSub($union, 'documentos')
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filasPorPagina)
            ->withQueryString();
    }

    /**
     * Convierte las líneas recibidas en líneas con montos calculados por el servidor.
     *
     * @param  list<array{producto_id: int|string, cantidad: string|float, precio_unitario?: string|null, porcentaje_descuento?: string|null, porcentaje_iva?: string|null}>  $lineas
     * @return list<array<string, mixed>>
     */
    private function construirLineas(array $lineas): array
    {
        if ($lineas === []) {
            throw new OperacionNoPermitidaException('El documento debe tener al menos un producto.');
        }

        $productos = Producto::query()
            ->whereIn('id', array_map(fn (array $linea): int => (int) $linea['producto_id'], $lineas))
            ->get()
            ->keyBy('id');

        return array_map(function (array $linea) use ($productos): array {
            $producto = $productos->get((int) $linea['producto_id']);

            if ($producto === null || ! $producto->activo) {
                throw new OperacionNoPermitidaException('Uno de los productos seleccionados no existe o está inactivo.');
            }

            return $this->calculadora->lineaDesdeProducto(
                $producto,
                (string) $linea['cantidad'],
                isset($linea['porcentaje_descuento']) ? (string) $linea['porcentaje_descuento'] : null,
                isset($linea['porcentaje_iva']) ? (string) $linea['porcentaje_iva'] : null,
                isset($linea['precio_unitario']) ? (string) $linea['precio_unitario'] : null,
            );
        }, array_values($lineas));
    }

    /**
     * "Guardar también en el catálogo": actualiza el precio base, el % de descuento y
     * el % de IVA del producto con los valores usados en la línea. El permiso se valida
     * en el FormRequest (sólo quien puede editar productos).
     *
     * @param  list<array{producto_id: int|string, precio_unitario?: string|null, porcentaje_descuento?: string|null, porcentaje_iva?: string|null, actualizar_catalogo?: bool}>  $lineas
     */
    private function actualizarCatalogo(array $lineas): void
    {
        foreach ($lineas as $linea) {
            if (! ($linea['actualizar_catalogo'] ?? false)) {
                continue;
            }

            $producto = Producto::query()->lockForUpdate()->find((int) $linea['producto_id']);

            if ($producto === null) {
                continue;
            }

            $this->catalogo->guardarProducto([
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'precio_base' => $linea['precio_unitario'] ?? (string) $producto->precio_base,
                'porcentaje_descuento' => $linea['porcentaje_descuento'] ?? (string) $producto->porcentaje_descuento,
                'porcentaje_iva' => $linea['porcentaje_iva'] ?? (string) $producto->porcentaje_iva,
                'activo' => $producto->activo,
            ], $producto);
        }
    }

    private function marcarCotizacionFacturada(int $cotizacionId, int $clienteId): void
    {
        $cotizacion = Cotizacion::query()->lockForUpdate()->findOrFail($cotizacionId);

        if (! $cotizacion->estado->puedeFacturarse()) {
            throw new OperacionNoPermitidaException(
                "La cotización {$cotizacion->numero} está {$cotizacion->estado->etiqueta()} y no puede facturarse."
            );
        }

        if ($cotizacion->cliente_id !== $clienteId) {
            throw new OperacionNoPermitidaException('La factura debe emitirse al mismo cliente de la cotización.');
        }

        $cotizacion->update(['estado' => EstadoCotizacion::Facturada]);
    }
}
