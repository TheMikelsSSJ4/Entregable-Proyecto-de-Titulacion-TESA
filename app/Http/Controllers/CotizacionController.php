<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCotizacionRequest;
use App\Http\Requests\UpdateCotizacionRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\FacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Cotizaciones maestro-detalle. El listado vive en el Módulo de Documentos.
 */
class CotizacionController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', Cotizacion::class);

        return view('cotizaciones.create', [
            'cotizacion' => new Cotizacion(['fecha_emision' => today()]),
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'razon_social', 'ruc_cedula']),
            'productos' => Producto::catalogoParaFormularios(),
            'lineasIniciales' => [],
            'clienteSeleccionado' => $request->integer('cliente') ?: null,
            'vendedorSeleccionado' => $request->user()->id,
            'vendedores' => Usuario::opcionesVendedor(),
            'puedeEditarCatalogo' => $request->user()->can('create', Producto::class),
        ]);
    }

    public function store(StoreCotizacionRequest $request, FacturacionService $facturacion): RedirectResponse
    {
        $cotizacion = $facturacion->registrarCotizacion($request->validated(), $request->user());

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} emitida por $".number_format((float) $cotizacion->total, 2).'.');
    }

    public function show(Cotizacion $cotizacion): View
    {
        Gate::authorize('view', $cotizacion);

        $cotizacion->load(['cliente', 'usuario', 'vendedor', 'detalles', 'facturas:id,cotizacion_id,numero_factura']);

        return view('cotizaciones.show', ['cotizacion' => $cotizacion]);
    }

    public function edit(Request $request, Cotizacion $cotizacion, FacturacionService $facturacion): View
    {
        Gate::authorize('update', $cotizacion);

        return view('cotizaciones.edit', [
            'cotizacion' => $cotizacion,
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'razon_social', 'ruc_cedula']),
            'productos' => Producto::catalogoParaFormularios(),
            'lineasIniciales' => $facturacion->lineasDesdeCotizacion($cotizacion),
            'clienteSeleccionado' => $cotizacion->cliente_id,
            'vendedorSeleccionado' => $cotizacion->vendedor_id,
            'vendedores' => Usuario::opcionesVendedor(),
            'puedeEditarCatalogo' => $request->user()->can('create', Producto::class),
        ]);
    }

    public function update(UpdateCotizacionRequest $request, Cotizacion $cotizacion, FacturacionService $facturacion): RedirectResponse
    {
        $facturacion->actualizarCotizacion($cotizacion, $request->validated());

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} actualizada.");
    }
}
