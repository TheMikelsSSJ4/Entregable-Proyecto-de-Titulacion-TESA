<?php

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Services\CarteraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Cliente::class);

        $buscar = $request->string('buscar')->trim()->limit(100, '')->toString();

        // Saldo pendiente por cliente calculado en una sola consulta (sin N+1).
        $saldoSubconsulta = Factura::query()
            ->selectRaw('COALESCE(SUM(facturas.total - COALESCE((SELECT SUM(p.monto_abonado) FROM pagos_abonos p WHERE p.factura_id = facturas.id), 0)), 0)')
            ->whereColumn('facturas.cliente_id', 'clientes.id')
            ->whereIn('facturas.estado', EstadoFactura::abiertos());

        $vencimientoSubconsulta = Factura::query()
            ->selectRaw('MIN(facturas.fecha_vencimiento)')
            ->whereColumn('facturas.cliente_id', 'clientes.id')
            ->whereIn('facturas.estado', EstadoFactura::abiertos());

        return view('clientes.index', [
            'buscar' => $buscar,
            'clientes' => Cliente::query()
                ->select('clientes.*')
                ->selectSub($saldoSubconsulta, 'saldo_pendiente')
                ->selectSub($vencimientoSubconsulta, 'vencimiento_mas_antiguo')
                ->buscar($buscar)
                ->orderBy('razon_social')
                ->paginate($this->filasPorPagina($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Cliente::class);

        return view('clientes.create', ['cliente' => new Cliente]);
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente {$cliente->razon_social} registrado.");
    }

    public function show(Request $request, Cliente $cliente, CarteraService $cartera): View
    {
        Gate::authorize('view', $cliente);

        $usuario = $request->user();

        return view('clientes.show', [
            'cliente' => $cliente,
            'resumen' => $cartera->resumenCliente($cliente),
            'primeraFactura' => $cliente->facturas()->min('fecha_emision'),
            'facturas' => $cliente->facturas()
                ->visiblesPara($usuario)
                ->withSum('pagos as total_abonado', 'monto_abonado')
                ->latest('fecha_emision')
                ->latest('id')
                ->paginate($this->filasPorPagina($request)),
            'cotizaciones' => $cliente->cotizaciones()
                ->visiblesPara($usuario)
                ->latest('fecha_emision')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('clientes.edit', ['cliente' => $cliente]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.show', $cliente)
            ->with('success', 'Datos del cliente actualizados.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('delete', $cliente);

        $documentos = $cliente->facturas()->count() + $cliente->cotizaciones()->count();

        if ($documentos > 0) {
            return back()->with('error', "No se puede eliminar a {$cliente->razon_social}: tiene {$documentos} documento(s) registrados.");
        }

        DB::transaction(fn () => $cliente->delete());

        return redirect()->route('clientes.index')
            ->with('success', "Cliente {$cliente->razon_social} eliminado.");
    }
}
