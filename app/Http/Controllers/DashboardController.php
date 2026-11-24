<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Services\CarteraService;
use App\Services\FacturacionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CarteraService $cartera, FacturacionService $facturacion): View
    {
        $usuario = $request->user();
        $verFinanzas = $usuario->can('ver-cartera');

        return view('dashboard', [
            'verFinanzas' => $verFinanzas,
            'ventasMes' => $facturacion->resumenVentasMes($usuario),
            'clientesRegistrados' => Cliente::count(),
            'cotizacionesPendientes' => Cotizacion::query()
                ->visiblesPara($usuario)
                ->whereIn('estado', [EstadoCotizacion::Pendiente->value, EstadoCotizacion::Aprobada->value])
                ->count(),
            'resumen' => $verFinanzas ? $cartera->resumen() : null,
            'recaudacionMes' => $verFinanzas ? $cartera->recaudacionMes() : null,
            'alertas' => $verFinanzas ? $cartera->alertasVencimiento(8) : collect(),
            'ultimosPagos' => $verFinanzas ? $cartera->ultimosPagos(5) : collect(),
            'ultimasFacturas' => $verFinanzas ? collect() : Factura::query()
                ->visiblesPara($usuario)
                ->with('cliente:id,razon_social')
                ->withSum('pagos as total_abonado', 'monto_abonado')
                ->latest('fecha_emision')
                ->latest('id')
                ->limit(6)
                ->get(),
        ]);
    }
}
