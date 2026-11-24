<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta {{ $cliente->razon_social }}</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Estado de cuenta · {{ $periodo }} · Generado el {{ now()->format('d/m/Y H:i') }}. Valores sujetos a verificación con el departamento contable.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Estado de cuenta por cliente',
        'numero' => 'Corte al '.today()->format('d/m/Y'),
        'detalle' => $periodo,
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            <td style="width: 52%; padding-right: 8px; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Cliente</p>
                    <p class="negrita" style="font-size: 11px; margin-top: 3px;">{{ $cliente->razon_social }}</p>
                    <p>{{ $cliente->tipoIdentificacion() }}: {{ $cliente->ruc_cedula }}</p>
                    <p class="muted">{{ $cliente->direccion }}</p>
                    <p class="muted">{{ $cliente->telefono }} {{ $cliente->correo ? '· '.$cliente->correo : '' }}</p>
                </div>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <div class="caja">
                    <p class="eyebrow">Situación actual</p>
                    <table style="margin-top: 3px;">
                        <tr><td class="muted">Facturado en el periodo</td><td class="der">{{ $fmt($totales_periodo['facturado']) }}</td></tr>
                        <tr><td class="muted">Abonado en el periodo</td><td class="der menta">{{ $fmt($totales_periodo['abonado']) }}</td></tr>
                        <tr><td class="muted">Facturas con saldo</td><td class="der">{{ $resumen['facturas_abiertas'] }}</td></tr>
                        <tr><td class="muted">Saldo en mora</td><td class="der cobre negrita">{{ $fmt($resumen['en_mora']) }}{{ $resumen['dias_mora_max'] > 0 ? ' · '.$resumen['dias_mora_max'].' días máx.' : '' }}</td></tr>
                        <tr><td class="negrita">Saldo total pendiente</td><td class="der negrita" style="font-size: 12px;">{{ $fmt($resumen['saldo']) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Facturas emitidas en el periodo ({{ $facturas->count() }})</p>
    <table class="tabla">
        <thead>
            <tr><th>Factura</th><th>Emisión</th><th>Vencimiento</th><th>Tipo</th><th class="der">Total</th><th class="der">Abonado</th><th class="der">Saldo</th><th class="der">Días mora</th></tr>
        </thead>
        <tbody>
            @forelse ($facturas as $factura)
                <tr>
                    <td>{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                    <td class="{{ $factura->estaVencida() ? 'cobre' : '' }}">{{ $factura->esCredito() ? $factura->fecha_vencimiento->format('d/m/Y') : 'Contado' }}</td>
                    <td>{{ $factura->tipo_transaccion->etiqueta() }}</td>
                    <td class="der">{{ $fmt($factura->total) }}</td>
                    <td class="der menta">{{ $fmt($factura->totalAbonado()) }}</td>
                    <td class="der negrita">{{ $fmt($factura->saldo()) }}</td>
                    <td class="der {{ $factura->estaVencida() ? 'cobre negrita' : '' }}">{{ $factura->diasMora() ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted centro">El cliente no tiene facturas emitidas en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Abonos y pagos recibidos en el periodo ({{ $pagos->count() }})</p>
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Factura</th><th>Método</th><th>Comprobante</th><th class="der">Monto</th></tr>
        </thead>
        <tbody>
            @forelse ($pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $pago->factura->numero_factura }}</td>
                    <td>{{ $pago->metodo_pago->etiqueta() }}</td>
                    <td>{{ $pago->comprobante_referencia ?? '—' }}</td>
                    <td class="der menta">+{{ $fmt($pago->monto_abonado) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted centro">Sin pagos registrados en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
