<?php

namespace App\Exports;

use App\Models\EmpresaConfiguracion;
use App\Models\Usuario;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte general del periodo: hojas "Resumen", "Por vendedor", "Antigüedad" y
 * "Principales deudores", con los mismos datos del PDF.
 */
class ReporteGeneralExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{
     *     resumen: array{cantidad: int, anuladas: int, subtotal: string, descuento: string, iva: string, total: string, contado: string, credito: string, cobrado: string, saldo: string},
     *     cantidad_pagos: int,
     *     vendedores: list<array{vendedor: Usuario, cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string, participacion: float}>,
     *     totales_vendedores: array{cantidad: int, total: string, contado: string, credito: string, cobrado: string, saldo: string, en_mora: string},
     *     cartera: array<string, mixed>
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
        $ventas = $this->datos['resumen'];
        $cartera = $this->datos['cartera'];
        $totales = $this->datos['totales_vendedores'];
        $diasAlerta = config('contable.dias_alerta_previa');

        $resumen = [
            ['Ventas', 'Total vendido', (float) $ventas['total'], $ventas['cantidad']],
            ['Ventas', 'Al contado', (float) $ventas['contado'], null],
            ['Ventas', 'A crédito', (float) $ventas['credito'], null],
            ['Ventas', 'Subtotal (base imponible)', (float) $ventas['subtotal'], null],
            ['Ventas', 'Descuentos', (float) $ventas['descuento'], null],
            ['Ventas', 'IVA', (float) $ventas['iva'], null],
            ['Ventas', 'Facturas anuladas', null, $ventas['anuladas']],
            ['Cobros', 'Cobrado en el periodo', (float) $ventas['cobrado'], $this->datos['cantidad_pagos']],
            ['Cobros', 'Saldo pendiente de las ventas', (float) $ventas['saldo'], null],
            ['Cartera', 'Cartera por cobrar', (float) $cartera['por_cobrar'], $cartera['facturas_abiertas']],
            ['Cartera', 'Al día', (float) $cartera['al_dia'], null],
            ['Cartera', "Por vencer ({$diasAlerta} días)", (float) $cartera['por_vencer_monto'], $cartera['facturas_por_vencer']],
            ['Cartera', 'En mora', (float) $cartera['en_mora'], $cartera['facturas_vencidas']],
            ['Cartera', 'Índice de morosidad', null, number_format($cartera['indice_morosidad'], 1).'%'],
        ];

        $vendedores = array_map(fn (array $fila): array => [
            $fila['vendedor']->nombre_completo,
            $fila['cantidad'],
            (float) $fila['total'],
            (float) $fila['contado'],
            (float) $fila['credito'],
            (float) $fila['cobrado'],
            (float) $fila['saldo'],
            (float) $fila['en_mora'],
            $fila['participacion'] / 100,
        ], $this->datos['vendedores']);

        $tramos = array_map(fn (array $tramo): array => [
            $tramo['etiqueta'],
            $tramo['cantidad'],
            (float) $tramo['monto'],
        ], $cartera['tramos']);

        $deudores = collect($cartera['top_deudores'])->map(fn (array $fila): array => [
            $fila['cliente']->razon_social,
            $fila['cliente']->ruc_cedula,
            $fila['facturas'],
            $fila['dias_mora'],
            (float) $fila['saldo'],
        ])->all();

        return [
            new HojaReporte(
                $this->empresa,
                'Reporte general del periodo',
                $this->periodo,
                'Resumen',
                ['Sección', 'Indicador', 'Monto', 'Cantidad'],
                $resumen,
                [2],
            ),
            new HojaReporte(
                $this->empresa,
                'Ventas por vendedor',
                $this->periodo,
                'Por vendedor',
                ['Vendedor', 'Facturas', 'Total vendido', 'Contado', 'Crédito', 'Cobrado', 'Saldo pendiente', 'Saldo en mora', 'Participación'],
                $vendedores,
                [2, 3, 4, 5, 6, 7],
                ['TOTAL', $totales['cantidad'], (float) $totales['total'], (float) $totales['contado'], (float) $totales['credito'], (float) $totales['cobrado'], (float) $totales['saldo'], (float) $totales['en_mora'], $totales['cantidad'] > 0 ? 1.0 : 0.0],
                [8 => '0.0%'],
            ),
            new HojaReporte(
                $this->empresa,
                'Antigüedad de saldos vencidos',
                $this->periodo,
                'Antigüedad',
                ['Tramo', 'Facturas', 'Saldo'],
                $tramos,
                [2],
                ['TOTAL EN MORA', $cartera['facturas_vencidas'], (float) $cartera['en_mora']],
            ),
            new HojaReporte(
                $this->empresa,
                'Principales deudores',
                $this->periodo,
                'Principales deudores',
                ['Cliente', 'RUC / Cédula', 'Facturas', 'Días de mora máx.', 'Saldo'],
                $deudores,
                [4],
            ),
        ];
    }
}
