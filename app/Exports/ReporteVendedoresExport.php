<?php

namespace App\Exports;

use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\Usuario;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de ventas por vendedor: hoja "Por vendedor" (resumen) y hoja "Detalle".
 */
class ReporteVendedoresExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{
     *     filas: list<array{vendedor: Usuario, cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string, participacion: float}>,
     *     facturas: iterable<Factura>,
     *     totales: array{cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string}
     * }  $datos
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
        $resumen = array_map(fn (array $fila): array => [
            $fila['vendedor']->nombre_completo,
            $fila['vendedor']->rol->nombre->value,
            $fila['cantidad'],
            (float) $fila['total'],
            (float) $fila['contado'],
            (float) $fila['credito'],
            (float) $fila['cobrado'],
            (float) $fila['saldo'],
            (float) $fila['en_mora'],
            $fila['participacion'] / 100,
        ], $this->datos['filas']);

        $detalle = [];

        foreach ($this->datos['facturas'] as $factura) {
            $detalle[] = [
                $factura->vendedor->nombre_completo,
                $factura->numero_factura,
                $factura->fecha_emision->format('d/m/Y'),
                $factura->cliente->razon_social,
                $factura->tipo_transaccion->etiqueta(),
                $factura->estado->etiqueta(),
                (float) $factura->total,
                (float) $factura->totalAbonado(),
                (float) $factura->saldo(),
            ];
        }

        usort($detalle, fn (array $a, array $b): int => [$a[0], $a[2]] <=> [$b[0], $b[2]]);

        $totales = $this->datos['totales'];

        return [
            new HojaReporte(
                $this->empresa,
                'Reporte de ventas por vendedor',
                $this->periodo,
                'Por vendedor',
                ['Vendedor', 'Rol', 'Facturas', 'Total vendido', 'Contado', 'Crédito', 'Cobrado', 'Saldo pendiente', 'Saldo en mora', 'Participación'],
                $resumen,
                [3, 4, 5, 6, 7, 8],
                ['TOTAL', '', $totales['cantidad'], (float) $totales['total'], (float) $totales['contado'], (float) $totales['credito'], (float) $totales['cobrado'], (float) $totales['saldo'], (float) $totales['en_mora'], $totales['cantidad'] > 0 ? 1.0 : 0.0],
                [9 => '0.0%'],
            ),
            new HojaReporte(
                $this->empresa,
                'Facturas por vendedor',
                $this->periodo,
                'Detalle',
                ['Vendedor', 'Factura', 'Emisión', 'Cliente', 'Tipo', 'Estado', 'Total', 'Abonado', 'Saldo'],
                $detalle,
                [6, 7, 8],
            ),
        ];
    }
}
