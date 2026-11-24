<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use App\Services\CatalogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Módulo de Productos: catálogo de facturación (sin control de stock).
 */
class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Producto::class);

        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:activos,inactivos'],
        ]);

        return view('productos.index', [
            'filtros' => $filtros,
            'productos' => Producto::query()
                ->buscar($filtros['buscar'] ?? null)
                ->when(($filtros['estado'] ?? null) === 'activos', fn ($q) => $q->where('activo', true))
                ->when(($filtros['estado'] ?? null) === 'inactivos', fn ($q) => $q->where('activo', false))
                ->orderByDesc('activo')
                ->orderBy('nombre')
                ->paginate($this->filasPorPagina($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Producto::class);

        return view('productos.create', [
            'producto' => new Producto(['porcentaje_descuento' => '0.00', 'porcentaje_iva' => config('contable.tarifas_iva')[0]]),
        ]);
    }

    public function store(StoreProductoRequest $request, CatalogoService $catalogo): RedirectResponse
    {
        $producto = $catalogo->guardarProducto($request->validated());

        return redirect()->route('productos.index')
            ->with('success', "Producto {$producto->codigo} registrado con precio final de $".number_format((float) $producto->precio_final, 2).'.');
    }

    public function edit(Producto $producto): View
    {
        Gate::authorize('update', $producto);

        return view('productos.edit', ['producto' => $producto]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto, CatalogoService $catalogo): RedirectResponse
    {
        $catalogo->guardarProducto($request->validated(), $producto);

        return redirect()->route('productos.index')
            ->with('success', "Producto {$producto->codigo} actualizado. Los documentos ya emitidos conservan sus precios originales.");
    }

    public function destroy(Producto $producto, CatalogoService $catalogo): RedirectResponse
    {
        Gate::authorize('delete', $producto);

        $catalogo->eliminarProducto($producto);

        return redirect()->route('productos.index')
            ->with('success', "Producto {$producto->codigo} eliminado.");
    }
}
