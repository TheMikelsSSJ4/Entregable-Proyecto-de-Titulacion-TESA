<?php

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\TipoTransaccion;
use App\Http\Requests\StoreFacturaRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\FacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Módulo de Transacciones: ventas al contado, a crédito y su historial de pagos.
 */
class FacturaController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Factura::class);

        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'vendedor' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::enum(TipoTransaccion::class)],
            'estado' => ['nullable', Rule::enum(EstadoFactura::class)],
        ]);

        return view('facturas.index', [
            'filtros' => $filtros,
            'vendedores' => $request->user()->accedeAFinanzas() ? Usuario::opcionesVendedor() : [],
            'facturas' => Factura::query()
                ->visiblesPara($request->user())
                ->with(['cliente:id,razon_social,ruc_cedula', 'usuario:id,nombre_completo,rol_id', 'vendedor:id,nombre_completo,rol_id'])
                ->withSum('pagos as total_abonado', 'monto_abonado')
                ->buscar($filtros['buscar'] ?? null)
                ->when($filtros['vendedor'] ?? null, fn ($q, int $vendedor) => $q->where('vendedor_id', $vendedor))
                ->when($filtros['tipo'] ?? null, fn ($q, string $tipo) => $q->where('tipo_transaccion', $tipo))
                ->when($filtros['estado'] ?? null, fn ($q, string $estado) => $q->where('estado', $estado))
                ->latest('fecha_emision')
                ->latest('id')
                ->paginate($this->filasPorPagina($request))
                ->withQueryString(),
        ]);
    }

    public function create(Request $request, FacturacionService $facturacion): View
    {
        Gate::authorize('create', Factura::class);

        $cotizacion = null;
        $lineas = [];

        if ($request->filled('cotizacion')) {
            $cotizacion = Cotizacion::with('cliente:id,razon_social')->findOrFail($request->integer('cotizacion'));
            Gate::authorize('view', $cotizacion);
            $lineas = $facturacion->lineasDesdeCotizacion($cotizacion);
        }

        return view('facturas.create', [
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'razon_social', 'ruc_cedula']),
            'productos' => Producto::catalogoParaFormularios(),
            'numeroSugerido' => $facturacion->sugerirNumeroFactura(),
            'cotizacion' => $cotizacion,
            'lineasIniciales' => $lineas,
            'clienteSeleccionado' => $cotizacion?->cliente_id ?? ($request->integer('cliente') ?: null),
            'vendedorSeleccionado' => $cotizacion?->vendedor_id ?? $request->user()->id,
            'vendedores' => Usuario::opcionesVendedor(),
            'puedeEditarCatalogo' => $request->user()->can('create', Producto::class),
        ]);
    }

    public function store(StoreFacturaRequest $request, FacturacionService $facturacion): RedirectResponse
    {
        $factura = $facturacion->registrarFactura($request->validated(), $request->user());

        $mensaje = $factura->esCredito()
            ? "Factura {$factura->numero_factura} registrada a crédito. Vence el {$factura->fecha_vencimiento->format('d/m/Y')}."
            : "Factura {$factura->numero_factura} registrada y pagada al contado.";

        return redirect()->route('facturas.show', $factura)->with('success', $mensaje);
    }

    public function show(Factura $factura): View
    {
        Gate::authorize('view', $factura);

        $factura->load([
            'cliente',
            'usuario',
            'vendedor',
            'cotizacion:id,estado',
            'detalles',
            'pagos' => fn ($q) => $q->with('usuario')->orderBy('fecha_pago')->orderBy('id'),
        ]);

        return view('facturas.show', ['factura' => $factura]);
    }

    public function anular(Factura $factura, FacturacionService $facturacion): RedirectResponse
    {
        Gate::authorize('anular', $factura);

        $facturacion->anularFactura($factura);

        return redirect()->route('facturas.show', $factura)
            ->with('success', "Factura {$factura->numero_factura} anulada.");
    }
}
