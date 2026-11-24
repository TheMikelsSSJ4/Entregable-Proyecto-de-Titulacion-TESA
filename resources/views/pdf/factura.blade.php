<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->numero_factura }}</title>
    @include('pdf._estilos')
</head>
<body>
    @php
        $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ',');
        $indicador = $factura->indicadorCartera();
    @endphp

    <div class="pie">
        Documento interno de control de cartera generado el {{ now()->format('d/m/Y H:i') }}. No reemplaza al comprobante electrónico autorizado por el SRI.
    </div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Factura · '.$factura->tipo_transaccion->etiqueta(),
        'numero' => 'N.º '.$factura->numero_factura,
        'detalle' => 'Emitida el '.$factura->fecha_emision->format('d/m/Y'),
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            <td style="width: 62%; padding-right: 8px; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Cliente</p>
                    <p class="negrita" style="font-size: 11px; margin-top: 3px;">{{ $factura->cliente->razon_social }}</p>
                    <p>{{ $factura->cliente->tipoIdentificacion() }}: {{ $factura->cliente->ruc_cedula }}</p>
                    <p class="muted">{{ $factura->cliente->direccion }}</p>
                    <p class="muted">{{ $factura->cliente->telefono }} {{ $factura->cliente->correo ? '· '.$factura->cliente->correo : '' }}</p>
                </div>
            </td>
            <td style="width: 38%; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Condiciones</p>
                    <table style="margin-top: 3px;">
                        <tr><td class="muted">Vendedor</td><td class="der negrita">{{ \Illuminate\Support\Str::upper($factura->vendedor->nombre_completo) }}</td></tr>
                        <tr><td class="muted">Forma de venta</td><td class="der negrita">{{ $factura->tipo_transaccion->etiqueta() }}</td></tr>
                        <tr><td class="muted">Vencimiento</td><td class="der">{{ $factura->esCredito() ? $factura->fecha_vencimiento->format('d/m/Y') : '—' }}</td></tr>
                        <tr><td class="muted">Plazo</td><td class="der">{{ $factura->esCredito() ? $factura->fecha_emision->diffInDays($factura->fecha_vencimiento).' días' : 'Contado' }}</td></tr>
                        <tr><td class="muted">Estado</td><td class="der"><span class="chip chip-{{ $indicador['tono'] }}">{{ $indicador['etiqueta'] }}</span></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="espacio"></div>

    @include('pdf._detalle', ['documento' => $factura])

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Pagos y abonos aplicados</p>
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Método</th><th>Comprobante</th><th class="der">Monto</th></tr>
        </thead>
        <tbody>
            @forelse ($factura->pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $pago->metodo_pago->etiqueta() }}</td>
                    <td>{{ $pago->comprobante_referencia ?? '—' }}</td>
                    <td class="der menta">+{{ $fmt($pago->monto_abonado) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted centro">Sin pagos registrados.</td></tr>
            @endforelse
            <tr><td colspan="3" class="negrita">Total abonado</td><td class="der negrita menta">{{ $fmt($factura->totalAbonado()) }}</td></tr>
            <tr><td colspan="3" class="negrita">Saldo pendiente</td><td class="der negrita {{ $factura->estaVencida() ? 'cobre' : '' }}">{{ $fmt($factura->saldo()) }}</td></tr>
        </tbody>
    </table>

    @if ($factura->estaVencida())
        <div class="espacio"></div>
        <div class="caja" style="border-color: #f6b4a3; background: #fff1ed;">
            <p class="cobre negrita">Factura vencida con {{ $factura->diasMora() }} días de mora.</p>
            <p class="muted">Agradecemos regularizar el saldo pendiente a la brevedad.</p>
        </div>
    @endif

    <div class="espacio"></div>
    <p class="muted">Venta realizada por <strong>{{ \Illuminate\Support\Str::upper($factura->vendedor->nombre_completo) }}</strong> · Registrada en el sistema por {{ $factura->usuario->nombre_completo }}.</p>
</body>
</html>
