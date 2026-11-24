<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $cotizacion->numero }}</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Proforma generada el {{ now()->format('d/m/Y H:i') }}. Documento sin valor tributario.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Cotización / Proforma',
        'numero' => $cotizacion->numero,
        'detalle' => 'Emitida el '.$cotizacion->fecha_emision->format('d/m/Y'),
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            <td style="width: 62%; padding-right: 8px; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Cliente</p>
                    <p class="negrita" style="font-size: 11px; margin-top: 3px;">{{ $cotizacion->cliente->razon_social }}</p>
                    <p>{{ $cotizacion->cliente->tipoIdentificacion() }}: {{ $cotizacion->cliente->ruc_cedula }}</p>
                    <p class="muted">{{ $cotizacion->cliente->direccion }}</p>
                </div>
            </td>
            <td style="width: 38%; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Vigencia</p>
                    <table style="margin-top: 3px;">
                        <tr><td class="muted">Emisión</td><td class="der">{{ $cotizacion->fecha_emision->format('d/m/Y') }}</td></tr>
                        <tr><td class="muted">Válida hasta</td><td class="der negrita">{{ $cotizacion->fecha_emision->copy()->addDays($validezDias)->format('d/m/Y') }}</td></tr>
                        <tr><td class="muted">Estado</td><td class="der"><span class="chip chip-{{ $cotizacion->estado->tono() === 'cobre' ? 'cobre' : ($cotizacion->estado->tono() === 'menta' ? 'menta' : 'oro') }}">{{ $cotizacion->estado->etiqueta() }}</span></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="espacio"></div>

    @include('pdf._detalle', ['documento' => $cotizacion])

    <div class="espacio"></div>
    <div class="caja">
        <p class="eyebrow">Condiciones comerciales</p>
        <p style="margin-top: 3px;">Precios vigentes del catálogo a la fecha de emisión. La presente cotización tiene una validez de {{ $validezDias }} días desde su emisión.</p>
        <p style="margin-top: 3px;">Vendedor: <strong>{{ \Illuminate\Support\Str::upper($cotizacion->vendedor->nombre_completo) }}</strong></p>
        <p class="muted">Registrada en el sistema por {{ $cotizacion->usuario->nombre_completo }}</p>
    </div>
</body>
</html>
