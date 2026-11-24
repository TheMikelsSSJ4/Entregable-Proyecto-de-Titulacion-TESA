<?php

namespace App\Exports;

use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de cartera por cobrar: hoja "Cartera" (detalle) y hoja "Antigüedad".
 */
class ReporteCarteraExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{facturas: iterable<Factura>, resumen: array<string, mixed>}  $datos
     */
    public function __construct(
        private readonly array $datos,
        private readonly EmpresaConfiguracion $empresa,
        private readonly string $periodo,
    ) {}

    /**
     * @return list<HojaReporte>
     */
    public function sheets(): array
    {
        $detalle = [];

        foreach ($this->datos['facturas'] as $factura) {
            $detalle[] = [
                $factura->cliente->razon_social,
                $factura->cliente->ruc_cedula,
                $factura->numero_factura,
                $factura->fecha_emision->format('d/m/Y'),
                $factura->fecha_vencimiento->format('d/m/Y'),
                (float) $factura->total,
                (float) $factura->totalAbonado(),
                (float) $factura->saldo(),
                $factura->diasMora(),
                $factura->indicadorCartera()['etiqueta'],
            ];
        }

        $resumen = $this->datos['resumen'];

        $tramos = array_map(
            fn (array $tramo): array => [$tramo['etiqueta'], $tramo['cantidad'], (float) $tramo['monto']],
            $resumen['tramos'],
        );

        return [
            new HojaReporte(
                $this->empresa,
                'Reporte de cartera por cobrar',
                $this->periodo,
                'Cartera',
                ['Cliente', 'RUC / Cédula', 'Factura', 'Emisión', 'Vencimiento', 'Total', 'Abonado', 'Saldo', 'Días de mora', 'Condición'],
                $detalle,
                [5, 6, 7],
                ['TOTAL CARTERA POR COBRAR', '', '', '', '', null, null, (float) $resumen['por_cobrar'], null, ''],
            ),
            new HojaReporte(
                $this->empresa,
                'Antigüedad de saldos vencidos · Índice de morosidad '.number_format($resumen['indice_morosidad'], 1).'%',
                $this->periodo,
                'Antigüedad',
                ['Tramo de mora', 'Facturas', 'Saldo vencido'],
                [
                    ...$tramos,
                    ['Al día (sin vencer)', $resumen['facturas_abiertas'] - $resumen['facturas_vencidas'], (float) $resumen['al_dia']],
                ],
                [2],
                ['TOTAL', $resumen['facturas_abiertas'], (float) $resumen['por_cobrar']],
            ),
        ];
    }
}
