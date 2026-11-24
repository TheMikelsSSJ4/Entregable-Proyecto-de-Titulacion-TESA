<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Services\CarteraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Módulo de Cartera y Alertas: saldos pendientes y días de mora (RF-05).
 * Los reportes exportables están en el Módulo de Reportes.
 */
class CarteraController extends Controller
{
    public function index(Request $request, CarteraService $cartera): View
    {
        Gate::authorize('ver-cartera');

        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'condicion' => ['nullable', Rule::in(['vencidas', 'por_vencer', 'al_dia'])],
        ]);

        return view('cartera.index', [
            'filtros' => $filtros,
            'resumen' => $cartera->resumen(),
            'facturas' => $cartera->listadoCartera($filtros, $this->filasPorPagina($request)),
            'primeraEmisionAbierta' => Factura::query()->abiertas()->min('fecha_emision'),
        ]);
    }
}
