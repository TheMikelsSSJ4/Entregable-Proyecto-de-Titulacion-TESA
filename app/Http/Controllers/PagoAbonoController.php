<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoAbonoRequest;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Services\CarteraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Registro y reversión de pagos/abonos (RF-04).
 */
class PagoAbonoController extends Controller
{
    public function create(Request $request, CarteraService $cartera): View
    {
        Gate::authorize('create', PagoAbono::class);

        return view('abonos.create', [
            'facturas' => $cartera->facturasConSaldo(),
            'facturaSeleccionada' => $request->integer('factura') ?: null,
        ]);
    }

    public function store(StorePagoAbonoRequest $request, CarteraService $cartera): RedirectResponse
    {
        $factura = Factura::findOrFail($request->integer('factura_id'));

        $pago = $cartera->registrarAbono(
            $factura,
            $request->safe()->except('factura_id'),
            $request->user(),
        );

        $factura->refresh();

        $mensaje = $factura->estaAbierta()
            ? 'Abono de $'.number_format((float) $pago->monto_abonado, 2).' registrado. Saldo pendiente: $'.number_format((float) $factura->saldo(), 2).'.'
            : "Abono registrado. La factura {$factura->numero_factura} quedó totalmente pagada.";

        return redirect()->route('facturas.show', $factura)->with('success', $mensaje);
    }

    public function destroy(PagoAbono $abono, CarteraService $cartera): RedirectResponse
    {
        Gate::authorize('delete', $abono);

        $factura = $cartera->revertirAbono($abono);

        return redirect()->route('facturas.show', $factura)
            ->with('success', 'Abono revertido. El saldo y el estado de la factura fueron recalculados.');
    }
}
