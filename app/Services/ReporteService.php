<?php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Enums\TipoTransaccion;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Datos de los reportes del Módulo de Reportes. La misma estructura alimenta
 * el PDF (DomPDF) y el Excel (maatwebsite/excel), así ambos formatos coinciden.
 */
class ReporteService
{
    public function __construct(private readonly CarteraService $cartera) {}

    /**
     * Reporte general de ventas y transacciones del periodo, opcionalmente de un solo vendedor.
     *
     * @return array{
     *     facturas: Collection<int, Factura>,
     *     pagos: Collection<int, PagoAbono>,
     *     resumen: array{cantidad: int, anuladas: int, subtotal: string, descuento: string, iva: string, total: string, contado: string, credito: string, cobrado: string, saldo: string}
     * }
     */
    public function ventas(CarbonInterface $desde, CarbonInterface $hasta, ?Usuario $vendedor = null): array
    {
        $facturas = Factura::query()
            ->emitidasEntre($desde, $hasta)
            ->when($vendedor, fn (Builder $q) => $q->where('vendedor_id', $vendedor->id))
            ->with(['cliente:id,razon_social,ruc_cedula', 'usuario:id,nombre_completo,rol_id', 'vendedor:id,nombre_completo,rol_id'])
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $pagos = $this->pagosEntre($desde, $hasta)
            ->when($vendedor, fn (Builder $q) => $q->whereHas('factura', fn (Builder $f) => $f->where('vendedor_id', $vendedor->id)))
            ->get();

        $validas = $facturas->reject(fn (Factura $f): bool => $f->estado === EstadoFactura::Anulada);

        return [
            'facturas' => $facturas,
            'pagos' => $pagos,
            'resumen' => [
                'cantidad' => $validas->count(),
                'anuladas' => $facturas->count() - $validas->count(),
                'subtotal' => $this->sumar($validas, 'subtotal'),
                'descuento' => $this->sumar($validas, 'descuento'),
                'iva' => $this->sumar($validas, 'iva'),
                'total' => $this->sumar($validas, 'total'),
                'contado' => $this->sumar($validas->where('tipo_transaccion', TipoTransaccion::Contado), 'total'),
                'credito' => $this->sumar($validas->where('tipo_transaccion', TipoTransaccion::Credito), 'total'),
                'cobrado' => $this->sumar($pagos, 'monto_abonado'),
                'saldo' => $validas->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->saldo(), 2), '0.00'),
            ],
        ];
    }

    /**
     * Ventas por vendedor: resumen de cada vendedor con facturas válidas en el periodo.
     * "Cobrado" y "saldo" se refieren a las facturas emitidas en el periodo.
     *
     * @return array{
     *     filas: list<array{vendedor: Usuario, cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string, participacion: float}>,
     *     facturas: Collection<int, Factura>,
     *     totales: array{cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string}
     * }
     */
    public function ventasPorVendedor(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $facturas = Factura::query()
            ->emitidasEntre($desde, $hasta)
            ->where('estado', '!=', EstadoFactura::Anulada->value)
            ->with(['cliente:id,razon_social,ruc_cedula', 'vendedor:id,nombre_completo,rol_id'])
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $totalGeneral = $this->sumar($facturas, 'total');

        $filas = $facturas
            ->groupBy('vendedor_id')
            ->map(function (\Illuminate\Support\Collection $grupo) use ($totalGeneral): array {
                $total = $this->sumar($grupo, 'total');

                return [
                    'vendedor' => $grupo->first()->vendedor,
                    'cantidad' => $grupo->count(),
                    'total' => $total,
                    'contado' => $this->sumar($grupo->where('tipo_transaccion', TipoTransaccion::Contado), 'total'),
                    'credito' => $this->sumar($grupo->where('tipo_transaccion', TipoTransaccion::Credito), 'total'),
                    'cobrado' => $grupo->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->totalAbonado(), 2), '0.00'),
                    'saldo' => $grupo->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->saldo(), 2), '0.00'),
                    'en_mora' => $grupo->filter->estaVencida()->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->saldo(), 2), '0.00'),
                    'participacion' => bccomp($totalGeneral, '0', 2) === 0
                        ? 0.0
                        : round((float) bcdiv(bcmul($total, '100', 4), $totalGeneral, 4), 1),
                ];
            })
            ->sortByDesc(fn (array $fila): float => (float) $fila['total'])
            ->values()
            ->all();

        $sumarFilas = fn (string $campo): string => array_reduce($filas, fn (string $s, array $f): string => bcadd($s, $f[$campo], 2), '0.00');

        return [
            'filas' => $filas,
            'facturas' => $facturas,
            'totales' => [
                'cantidad' => $facturas->count(),
                'total' => $totalGeneral,
                'contado' => $sumarFilas('contado'),
                'credito' => $sumarFilas('credito'),
                'cobrado' => $sumarFilas('cobrado'),
                'saldo' => $sumarFilas('saldo'),
                'en_mora' => $sumarFilas('en_mora'),
            ],
        ];
    }

    /**
     * Reporte general del periodo en un solo documento: resumen de ventas y cobros,
     * ventas por vendedor y cartera (antigüedad de saldos y principales deudores)
     * de las facturas emitidas en el periodo.
     *
     * @return array{
     *     resumen: array{cantidad: int, anuladas: int, subtotal: string, descuento: string, iva: string, total: string, contado: string, credito: string, cobrado: string, saldo: string},
     *     cantidad_pagos: int,
     *     vendedores: list<array{vendedor: Usuario, cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string, participacion: float}>,
     *     totales_vendedores: array{cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string},
     *     cartera: array<string, mixed>
     * }
     */
    public function general(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $ventas = $this->ventas($desde, $hasta);
        $vendedores = $this->ventasPorVendedor($desde, $hasta);

        return [
            'resumen' => $ventas['resumen'],
            'cantidad_pagos' => $ventas['pagos']->count(),
            'vendedores' => $vendedores['filas'],
            'totales_vendedores' => $vendedores['totales'],
            'cartera' => $this->cartera->resumen($desde, $hasta),
        ];
    }

    /**
     * Reporte de cartera por cobrar: facturas pendientes y vencidas emitidas en el periodo.
     *
     * @return array{facturas: Collection<int, Factura>, resumen: array<string, mixed>}
     */
    public function cartera(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        return [
            'facturas' => $this->cartera->facturasConSaldo($desde, $hasta),
            'resumen' => $this->cartera->resumen($desde, $hasta),
        ];
    }

    /**
     * Estado de cuenta de un cliente: facturas y abonos del periodo, más su
     * saldo y mora actuales.
     *
     * @return array{
     *     cliente: Cliente,
     *     facturas: Collection<int, Factura>,
     *     pagos: Collection<int, PagoAbono>,
     *     resumen: array{saldo: string, en_mora: string, facturas_abiertas: int, dias_mora_max: int},
     *     totales_periodo: array{facturado: string, abonado: string}
     * }
     */
    public function estadoCuenta(Cliente $cliente, CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $facturas = $cliente->facturas()
            ->where('estado', '!=', EstadoFactura::Anulada->value)
            ->emitidasEntre($desde, $hasta)
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $pagos = $this->pagosEntre($desde, $hasta)
            ->whereHas('factura', fn (Builder $q) => $q->whereBelongsTo($cliente))
            ->get();

        return [
            'cliente' => $cliente,
            'facturas' => $facturas,
            'pagos' => $pagos,
            'resumen' => $this->cartera->resumenCliente($cliente),
            'totales_periodo' => [
                'facturado' => $this->sumar($facturas, 'total'),
                'abonado' => $this->sumar($pagos, 'monto_abonado'),
            ],
        ];
    }

    /**
     * @return Builder<PagoAbono>
     */
    private function pagosEntre(CarbonInterface $desde, CarbonInterface $hasta): Builder
    {
        return PagoAbono::query()
            ->whereBetween('fecha_pago', [$desde->toDateString(), $hasta->toDateString()])
            ->with([
                'factura:id,numero_factura,cliente_id,tipo_transaccion',
                'factura.cliente:id,razon_social',
                'usuario:id,nombre_completo,rol_id',
            ])
            ->orderBy('fecha_pago')
            ->orderBy('id');
    }

    /**
     * @param  iterable<Model>  $modelos
     */
    private function sumar(iterable $modelos, string $campo): string
    {
        $suma = '0.00';

        foreach ($modelos as $modelo) {
            $suma = bcadd($suma, (string) $modelo->{$campo}, 2);
        }

        return $suma;
    }
}
