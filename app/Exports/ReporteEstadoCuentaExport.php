<?php

namespace App\Exports;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\PagoAbono;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Estado de cuenta de un cliente: hoja "Facturas" y hoja "Abonos".
 */
class ReporteEstadoCuentaExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{cliente: Cliente, facturas: iterable<Factura>, pagos: iterable<PagoAbono>, resumen: array<string, mixed>, totales_periodo: array{facturado: string, abonado: string}}  $datos
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
        $cliente = $this->datos['cliente'];
        $titulo = "Estado de cuenta · {$cliente->razon_social} ({$cliente->ruc_cedula})";

        $facturas = [];

        foreach ($this->datos['facturas'] as $factura) {
            $facturas[] = [
                $factura->numero_factura,
                $factura->fecha_emision->format('d/m/Y'),
                $factura->fecha_vencimiento->format('d/m/Y'),
                $factura->tipo_transaccion->etiqueta(),
                (float) $factura->total,
                (float) $factura->totalAbonado(),
                (float) $factura->saldo(),
                $factura->diasMora(),
                $factura->indicadorCartera()['etiqueta'],
            ];
        }

        $abonos = [];

        foreach ($this->datos['pagos'] as $pago) {
            $abonos[] = [
                $pago->fecha_pago->format('d/m/Y'),
                $pago->factura->numero_factura,
                $pago->metodo_pago->etiqueta(),
                (string) ($pago->comprobante_referencia ?? ''),
                (float) $pago->monto_abonado,
            ];
        }

        $resumen = $this->datos['resumen'];

        return [
            new HojaReporte(
                $this->empresa,
                $titulo,
                $this->periodo.' · Saldo actual $'.number_format((float) $resumen['saldo'], 2).' · En mora $'.number_format((float) $resumen['en_mora'], 2),
                'Facturas',
                ['Factura', 'Emisión', 'Vencimiento', 'Tipo', 'Total', 'Abonado', 'Saldo', 'Días de mora', 'Condición'],
                $facturas,
                [4, 5, 6],
                ['TOTAL DEL PERIODO', '', '', '', (float) $this->datos['totales_periodo']['facturado'], null, null, null, ''],
            ),
            new HojaReporte(
                $this->empresa,
                $titulo,
                $this->periodo,
                'Abonos',
                ['Fecha', 'Factura', 'Método', 'Comprobante', 'Monto'],
                $abonos,
                [4],
                ['TOTAL ABONADO', '', '', '', (float) $this->datos['totales_periodo']['abonado']],
            ),
        ];
    }
}
