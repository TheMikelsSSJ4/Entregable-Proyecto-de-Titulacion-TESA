<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Factura;
use App\Services\FacturacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Módulo de Documentos: historial unificado de facturas y cotizaciones con PDF.
 */
class DocumentoController extends Controller
{
    public function __invoke(Request $request, FacturacionService $facturacion): View
    {
        Gate::authorize('viewAny', Factura::class);

        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::in(['facturas', 'cotizaciones'])],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        return view('documentos.index', [
            'filtros' => $filtros,
            'documentos' => $facturacion->historialDocumentos($request->user(), $filtros, $this->filasPorPagina($request)),
            'puedeCotizar' => $request->user()->can('create', Cotizacion::class),
        ]);
    }
}
