<?php

namespace App\Http\Controllers;

use App\Exports\ReporteCarteraExport;
use App\Exports\ReporteEstadoCuentaExport;
use App\Exports\ReporteGeneralExport;
use App\Exports\ReporteVendedoresExport;
use App\Exports\ReporteVentasExport;
use App\Http\Requests\ExportarReporteRequest;
use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Models\Usuario;
use App\Services\ReporteService;
use App\Services\VistaPreviaExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Módulo de Reportes: reporte general del periodo, ventas (todas o de un vendedor),
 * ventas por vendedor, cartera por cobrar y estado de cuenta, con vista previa en
 * pantalla y descarga en PDF y Excel.
 */
class ReporteController extends Controller
{
    public function index(Request $request, ReporteService $reportes): View
    {
        Gate::authorize('ver-reportes');

        $filtros = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'cliente_id' => ['nullable', 'integer'],
            'vendedor_id' => ['nullable', 'integer'],
        ]);

        $desde = Carbon::parse($filtros['fecha_inicio'] ?? now()->startOfYear()->toDateString())->startOfDay();
        $hasta = Carbon::parse($filtros['fecha_fin'] ?? today()->toDateString())->startOfDay();

        return view('reportes.index', [
            'fechaInicio' => $desde->toDateString(),
            'fechaFin' => $hasta->toDateString(),
            'clienteId' => $filtros['cliente_id'] ?? null,
            'vendedorId' => $filtros['vendedor_id'] ?? null,
            'vendedores' => Usuario::opcionesVendedor(),
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'razon_social', 'ruc_cedula']),
            'vistaPrevia' => $hasta->gte($desde)
                ? $reportes->ventas($desde, $hasta, isset($filtros['vendedor_id']) ? Usuario::find($filtros['vendedor_id']) : null)['resumen']
                : null,
        ]);
    }

    /**
     * Vista previa en pantalla, antes de descargar, de cómo queda el reporte:
     * - PDF: la misma plantilla del PDF sobre una hoja A4.
     * - Excel: el mismo libro .xlsx, hoja por hoja, como tablas.
     * Se muestra en un marco de la pantalla de Reportes.
     */
    public function vistaPrevia(ExportarReporteRequest $request, string $tipo, string $formato, ReporteService $reportes, VistaPreviaExcel $vistaExcel): View
    {
        $empresa = EmpresaConfiguracion::actual();
        $periodo = $request->periodoTexto();
        $datos = $this->datosDelReporte($request, $tipo, $reportes);

        if ($formato === 'xlsx') {
            return view('reportes.vista-previa-excel', [
                ...$vistaExcel->hojas($this->exportacionExcel($tipo, $datos, $empresa, $periodo)),
                'periodo' => $periodo,
            ]);
        }

        return view("pdf.reporte-{$tipo}", [
            ...$datos,
            'empresa' => $empresa,
            'periodo' => $periodo,
            'vistaPrevia' => true,
            'orientacion' => $this->orientacion($tipo),
        ]);
    }

    public function exportar(ExportarReporteRequest $request, string $tipo, string $formato, ReporteService $reportes): Response
    {
        $empresa = EmpresaConfiguracion::actual();
        $periodo = $request->periodoTexto();
        $sufijo = $request->desde()->format('Y-m-d').'_'.$request->hasta()->format('Y-m-d');
        $datos = $this->datosDelReporte($request, $tipo, $reportes);

        $nombre = match ($tipo) {
            'ventas' => $request->vendedor() ? 'Reporte-Ventas-'.Str::slug($request->vendedor()->nombre_completo)."-{$sufijo}" : "Reporte-Ventas-{$sufijo}",
            'general' => "Reporte-General-{$sufijo}",
            'vendedores' => "Reporte-Ventas-por-Vendedor-{$sufijo}",
            'cartera' => "Reporte-Cartera-{$sufijo}",
            'estado-cuenta' => "Estado-Cuenta-{$datos['cliente']->ruc_cedula}-{$sufijo}",
        };

        if ($formato === 'xlsx') {
            return Excel::download($this->exportacionExcel($tipo, $datos, $empresa, $periodo), "{$nombre}.xlsx");
        }

        return Pdf::loadView("pdf.reporte-{$tipo}", [...$datos, 'empresa' => $empresa, 'periodo' => $periodo])
            ->setPaper('a4', $this->orientacion($tipo))
            ->download("{$nombre}.pdf");
    }

    /**
     * Libro de Excel de cada reporte (se usa en la descarga y en la vista previa).
     *
     * @param  array<string, mixed>  $datos
     */
    private function exportacionExcel(string $tipo, array $datos, EmpresaConfiguracion $empresa, string $periodo): Export
    {
        return match ($tipo) {
            'ventas' => new ReporteVentasExport($datos, $empresa, $periodo),
            'general' => new ReporteGeneralExport($datos, $empresa, $periodo),
            'vendedores' => new ReporteVendedoresExport($datos, $empresa, $periodo),
            'cartera' => new ReporteCarteraExport($datos, $empresa, $periodo),
            'estado-cuenta' => new ReporteEstadoCuentaExport($datos, $empresa, $periodo),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDelReporte(ExportarReporteRequest $request, string $tipo, ReporteService $reportes): array
    {
        return match ($tipo) {
            'ventas' => $reportes->ventas($request->desde(), $request->hasta(), $request->vendedor()),
            'general' => $reportes->general($request->desde(), $request->hasta()),
            'vendedores' => $reportes->ventasPorVendedor($request->desde(), $request->hasta()),
            'cartera' => $reportes->cartera($request->desde(), $request->hasta()),
            'estado-cuenta' => $reportes->estadoCuenta($request->cliente(), $request->desde(), $request->hasta()),
        };
    }

    /**
     * Orientación de la hoja A4: el estado de cuenta es vertical, el resto horizontal.
     */
    private function orientacion(string $tipo): string
    {
        return $tipo === 'estado-cuenta' ? 'portrait' : 'landscape';
    }
}
