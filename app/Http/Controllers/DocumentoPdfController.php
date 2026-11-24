<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportación a PDF de facturas y cotizaciones (RF-03) con laravel-dompdf.
 * Con ?imprimir=1 el PDF se abre en el navegador (listo para imprimir);
 * sin el parámetro, se descarga.
 */
class DocumentoPdfController extends Controller
{
    public function factura(Request $request, Factura $factura): Response
    {
        Gate::authorize('view', $factura);

        $factura->load(['cliente', 'usuario', 'vendedor', 'detalles', 'pagos' => fn ($q) => $q->orderBy('fecha_pago')->orderBy('id')]);

        $pdf = Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'empresa' => EmpresaConfiguracion::actual(),
        ])->setPaper('a4');

        $nombre = "Factura-{$factura->numero_factura}.pdf";

        return $request->boolean('imprimir') ? $pdf->stream($nombre) : $pdf->download($nombre);
    }

    public function cotizacion(Request $request, Cotizacion $cotizacion): Response
    {
        Gate::authorize('view', $cotizacion);

        $cotizacion->load(['cliente', 'usuario', 'vendedor', 'detalles']);

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'empresa' => EmpresaConfiguracion::actual(),
            'validezDias' => (int) config('contable.validez_cotizacion_dias'),
        ])->setPaper('a4');

        $nombre = "Cotizacion-{$cotizacion->numero}.pdf";

        return $request->boolean('imprimir') ? $pdf->stream($nombre) : $pdf->download($nombre);
    }
}
