<?php

namespace App\Exports;

use App\Enums\EstadoFactura;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\PagoAbono;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte general de ventas y transacciones: hoja "Ventas" y hoja "Cobros".
 */
class ReporteVentasExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{facturas: iterable<Factura>, pagos: iterable<PagoAbono>, resumen: array<string, mixed>}  $datos
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
        $ventas = [];

        foreach ($this->datos['facturas'] as $factura) {
            $anulada = $factura->estado === EstadoFactura::Anulada;
            $ventas[] = [
                $factura->numero_factura,
                $factura->fecha_emision->format('d/m/Y'),
                $factura->cliente->razon_social,
                $factura->cliente->ruc_cedula,
                $factura->tipo_transaccion->etiqueta(),
                $factura->estado->etiqueta(),
                (float) $factura->subtotal,
                (float) $factura->descuento,
                (float) $factura->iva,
                (float) $factura->total,
                $anulada ? 0.0 : (float) $factura->totalAbonado(),
                (float) $factura->saldo(),
                $factura->vendedor->nombre_completo,
                $factura->usuario->nombre_completo,
            ];
        }

        $resumen = $this->datos['resumen'];

        $cobros = [];

        foreach ($this->datos['pagos'] as $pago) {
            $cobros[] = [
                $pago->fecha_pago->format('d/m/Y'),
                $pago->factura->numero_factura,
                $pago->factura->cliente->razon_social,
                $pago->factura->tipo_transaccion->etiqueta(),
                $pago->metodo_pago->etiqueta(),
                (string) ($pago->comprobante_referencia ?? ''),
                (float) $pago->monto_abonado,
                $pago->usuario->nombre_completo,
            ];
        }

        return [
            new HojaReporte(
                $this->empresa,
                'Reporte general de ventas y transacciones',
                $this->periodo,
                'Ventas',
                ['N.º factura', 'Emisión', 'Cliente', 'RUC / Cédula', 'Tipo', 'Estado', 'Subtotal', 'Descuento', 'IVA', 'Total', 'Abonado', 'Saldo', 'Vendedor', 'Registrada por'],
                $ventas,
                [6, 7, 8, 9, 10, 11],
                ["TOTAL ({$resumen['cantidad']} facturas válidas)", '', '', '', '', '', (float) $resumen['subtotal'], (float) $resumen['descuento'], (float) $resumen['iva'], (float) $resumen['total'], null, (float) $resumen['saldo'], '', ''],
            ),
            new HojaReporte(
                $this->empresa,
                'Cobros recibidos (ventas al contado y abonos)',
                $this->periodo,
                'Cobros',
                ['Fecha', 'Factura', 'Cliente', 'Tipo de venta', 'Método', 'Comprobante', 'Monto', 'Registrado por'],
                $cobros,
                [6],
                ['TOTAL COBRADO', '', '', '', '', '', (float) $resumen['cobrado'], ''],
            ),
        ];
    }
}
