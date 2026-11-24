<?php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Exceptions\OperacionNoPermitidaException;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Control de cartera por cobrar: abonos, saldos, mora y alertas (RF-04, RF-05).
 */
class CarteraService
{
    /**
     * Aplica un pago parcial o total a una factura a crédito y recalcula su estado.
     * La fila de la factura se bloquea (SELECT ... FOR UPDATE) para que dos abonos
     * simultáneos no puedan superar el saldo.
     *
     * @param  array{fecha_pago: string, monto_abonado: string|float, metodo_pago: string, comprobante_referencia?: string|null}  $datos
     */
    public function registrarAbono(Factura $factura, array $datos, Usuario $usuario): PagoAbono
    {
        return DB::transaction(function () use ($factura, $datos, $usuario): PagoAbono {
            $factura = Factura::query()->lockForUpdate()->findOrFail($factura->id);

            if (! $factura->estaAbierta()) {
                throw new OperacionNoPermitidaException(
                    "La factura {$factura->numero_factura} está {$factura->estado->etiqueta()} y no admite abonos."
                );
            }

            $fechaPago = Carbon::parse($datos['fecha_pago'])->startOfDay();

            if ($fechaPago->lt($factura->fecha_emision)) {
                throw new OperacionNoPermitidaException('La fecha del pago no puede ser anterior a la emisión de la factura.');
            }

            $saldo = $factura->saldo();
            $monto = bcadd((string) $datos['monto_abonado'], '0', 2);

            if (bccomp($monto, $saldo, 2) > 0) {
                throw new OperacionNoPermitidaException(
                    'El abono de $'.number_format((float) $monto, 2).' supera el saldo pendiente de $'.number_format((float) $saldo, 2).'.'
                );
            }

            $pago = $factura->pagos()->create([
                'usuario_id' => $usuario->id,
                'fecha_pago' => $fechaPago,
                'monto_abonado' => $monto,
                'metodo_pago' => $datos['metodo_pago'],
                'comprobante_referencia' => $datos['comprobante_referencia'] ?? null,
            ]);

            $nuevoSaldo = bcsub($saldo, $monto, 2);

            $factura->update([
                'estado' => bccomp($nuevoSaldo, '0', 2) === 0 ? EstadoFactura::Pagada : EstadoFactura::Parcial,
            ]);

            return $pago;
        });
    }

    /**
     * Elimina un abono registrado por error (sólo Gerencia) y recalcula el estado.
     */
    public function revertirAbono(PagoAbono $pago): Factura
    {
        return DB::transaction(function () use ($pago): Factura {
            $factura = Factura::query()->lockForUpdate()->findOrFail($pago->factura_id);

            if ($factura->estado === EstadoFactura::Anulada) {
                throw new OperacionNoPermitidaException('No se pueden modificar pagos de una factura anulada.');
            }

            $pago->delete();

            $abonado = $factura->totalAbonado();

            $factura->update([
                'estado' => match (true) {
                    bccomp($abonado, '0', 2) === 0 => EstadoFactura::Pendiente,
                    bccomp($abonado, (string) $factura->total, 2) >= 0 => EstadoFactura::Pagada,
                    default => EstadoFactura::Parcial,
                },
            ]);

            return $factura;
        });
    }

    /**
     * Facturas con saldo (formulario de abonos y reporte de cartera). El rango
     * opcional filtra por fecha de emisión.
     *
     * @return Collection<int, Factura>
     */
    public function facturasConSaldo(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): Collection
    {
        return Factura::query()
            ->abiertas()
            ->emitidasEntre($desde, $hasta)
            ->with('cliente:id,razon_social,ruc_cedula')
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get();
    }

    /**
     * Listado paginado de cartera con filtros de condición (vencidas, por vencer, al día).
     *
     * @param  array{buscar?: string|null, condicion?: string|null}  $filtros
     */
    public function listadoCartera(array $filtros, int $filasPorPagina = 10): LengthAwarePaginator
    {
        $condicion = $filtros['condicion'] ?? null;

        return Factura::query()
            ->abiertas()
            ->with('cliente:id,razon_social,ruc_cedula,telefono')
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->buscar($filtros['buscar'] ?? null)
            ->when($condicion === 'vencidas', fn (Builder $q) => $q->vencidas())
            ->when($condicion === 'por_vencer', fn (Builder $q) => $q->porVencer())
            ->when($condicion === 'al_dia', fn (Builder $q) => $q->whereDate('fecha_vencimiento', '>=', today()))
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->paginate($filasPorPagina)
            ->withQueryString();
    }

    /**
     * Alertas de vencimiento: facturas vencidas y próximas a vencer (más urgentes primero).
     *
     * @return Collection<int, Factura>
     */
    public function alertasVencimiento(int $limite = 6): Collection
    {
        return Factura::query()
            ->abiertas()
            ->whereDate('fecha_vencimiento', '<=', today()->addDays((int) config('contable.dias_alerta_previa')))
            ->with('cliente:id,razon_social')
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Totales de cartera, antigüedad de saldos vencidos y principales deudores.
     *
     * @return array{
     *     por_cobrar: string,
     *     en_mora: string,
     *     al_dia: string,
     *     por_vencer_monto: string,
     *     facturas_abiertas: int,
     *     facturas_vencidas: int,
     *     facturas_por_vencer: int,
     *     alertas_activas: int,
     *     indice_morosidad: float,
     *     tramos: list<array{desde: int, hasta: int|null, etiqueta: string, monto: string, cantidad: int}>,
     *     top_deudores: \Illuminate\Support\Collection<int, array{cliente: Cliente, saldo: string, facturas: int, dias_mora: int}>
     * }
     */
    public function resumen(?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): array
    {
        $abiertas = Factura::query()
            ->abiertas()
            ->emitidasEntre($desde, $hasta)
            ->with('cliente:id,razon_social,ruc_cedula')
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->get();

        $porCobrar = '0.00';
        $enMora = '0.00';
        $porVencerMonto = '0.00';
        $vencidas = 0;
        $porVencer = 0;

        $tramos = array_map(
            fn (array $tramo): array => $tramo + ['monto' => '0.00', 'cantidad' => 0],
            config('contable.tramos_mora'),
        );

        foreach ($abiertas as $factura) {
            $saldo = $factura->saldo();
            $porCobrar = bcadd($porCobrar, $saldo, 2);
            $dias = $factura->diasMora();

            if ($dias > 0) {
                $enMora = bcadd($enMora, $saldo, 2);
                $vencidas++;

                foreach ($tramos as $indice => $tramo) {
                    if ($dias >= $tramo['desde'] && ($tramo['hasta'] === null || $dias <= $tramo['hasta'])) {
                        $tramos[$indice]['monto'] = bcadd($tramo['monto'], $saldo, 2);
                        $tramos[$indice]['cantidad']++;
                        break;
                    }
                }
            } elseif ($factura->estaPorVencer()) {
                $porVencer++;
                $porVencerMonto = bcadd($porVencerMonto, $saldo, 2);
            }
        }

        $topDeudores = $abiertas
            ->groupBy('cliente_id')
            ->map(fn (\Illuminate\Support\Collection $facturas): array => [
                'cliente' => $facturas->first()->cliente,
                'saldo' => $facturas->reduce(fn (string $suma, Factura $f): string => bcadd($suma, $f->saldo(), 2), '0.00'),
                'facturas' => $facturas->count(),
                'dias_mora' => (int) $facturas->max(fn (Factura $f): int => $f->diasMora()),
            ])
            ->sortByDesc(fn (array $fila): float => (float) $fila['saldo'])
            ->take(5)
            ->values();

        return [
            'por_cobrar' => $porCobrar,
            'en_mora' => $enMora,
            'al_dia' => bcsub($porCobrar, $enMora, 2),
            'por_vencer_monto' => $porVencerMonto,
            'facturas_abiertas' => $abiertas->count(),
            'facturas_vencidas' => $vencidas,
            'facturas_por_vencer' => $porVencer,
            'alertas_activas' => $vencidas + $porVencer,
            'indice_morosidad' => bccomp($porCobrar, '0', 2) === 0
                ? 0.0
                : round((float) bcdiv(bcmul($enMora, '100', 4), $porCobrar, 4), 1),
            'tramos' => $tramos,
            'top_deudores' => $topDeudores,
        ];
    }

    /**
     * Total recaudado (contado + abonos) en el mes en curso.
     *
     * @return array{total: string, cantidad: int}
     */
    public function recaudacionMes(): array
    {
        $fila = PagoAbono::query()
            ->whereBetween('fecha_pago', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(monto_abonado), 0) AS total')
            ->toBase()
            ->first();

        return [
            'total' => bcadd((string) $fila->total, '0', 2),
            'cantidad' => (int) $fila->cantidad,
        ];
    }

    /**
     * Últimos pagos recibidos para el feed del Dashboard.
     *
     * @return Collection<int, PagoAbono>
     */
    public function ultimosPagos(int $limite = 5): Collection
    {
        return PagoAbono::query()
            ->with(['factura:id,numero_factura,cliente_id,tipo_transaccion', 'factura.cliente:id,razon_social'])
            ->latest('fecha_pago')
            ->latest('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Saldo pendiente y mora de un cliente (estado de cuenta).
     *
     * @return array{saldo: string, en_mora: string, facturas_abiertas: int, dias_mora_max: int}
     */
    public function resumenCliente(Cliente $cliente): array
    {
        $abiertas = $cliente->facturas()
            ->abiertas()
            ->withSum('pagos as total_abonado', 'monto_abonado')
            ->get();

        return [
            'saldo' => $abiertas->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->saldo(), 2), '0.00'),
            'en_mora' => $abiertas->filter->estaVencida()->reduce(fn (string $s, Factura $f): string => bcadd($s, $f->saldo(), 2), '0.00'),
            'facturas_abiertas' => $abiertas->count(),
            'dias_mora_max' => (int) $abiertas->max(fn (Factura $f): int => $f->diasMora()),
        ];
    }
}
