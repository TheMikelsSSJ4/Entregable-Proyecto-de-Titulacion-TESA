<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Reporte general de ventas · {{ $periodo }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombre_completo }}. Documento interno; no reemplaza comprobantes del SRI.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Reporte general de ventas y transacciones',
        'numero' => $periodo,
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            @foreach ([
                ['Ventas del periodo', $resumen['total'], '', $resumen['cantidad'].' facturas'],
                ['Al contado', $resumen['contado'], 'menta', ''],
                ['A crédito', $resumen['credito'], 'violeta', ''],
                ['Cobrado en el periodo', $resumen['cobrado'], 'menta', $pagos->count().' pagos'],
                ['Saldo por cobrar', $resumen['saldo'], 'cobre', ''],
            ] as [$etiqueta, $monto, $clase, $nota])
                <td style="width: 20%; padding: 0 {{ $loop->last ? '0' : '5px' }} 0 0;">
                    <div class="caja">
                        <p class="eyebrow">{{ $etiqueta }}</p>
                        <p class="negrita {{ $clase }}" style="font-size: 13px; margin-top: 3px;">{{ $fmt($monto) }}</p>
                        @if ($nota)<p class="muted">{{ $nota }}</p>@endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Facturas emitidas ({{ $facturas->count() }}{{ $resumen['anuladas'] ? ', '.$resumen['anuladas'].' anuladas' : '' }})</p>
    <table class="tabla">
        <thead>
            <tr>
                <th>Factura</th><th>Emisión</th><th>Cliente</th><th>Vendedor</th><th>Tipo</th><th>Estado</th>
                <th class="der">Subtotal</th><th class="der">Desc.</th><th class="der">IVA</th><th class="der">Total</th><th class="der">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($facturas as $factura)
                @php $anulada = $factura->estado === \App\Enums\EstadoFactura::Anulada; @endphp
                <tr style="{{ $anulada ? 'color: #9aa1b0; text-decoration: line-through;' : '' }}">
                    <td>{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                    <td>{{ $factura->cliente->razon_social }}</td>
                    <td>{{ $factura->vendedor->nombre_completo }}</td>
                    <td>{{ $factura->tipo_transaccion->etiqueta() }}</td>
                    <td>{{ $factura->estado->etiqueta() }}</td>
                    <td class="der">{{ $fmt($factura->subtotal) }}</td>
                    <td class="der">{{ (float) $factura->descuento > 0 ? $fmt($factura->descuento) : '—' }}</td>
                    <td class="der">{{ $fmt($factura->iva) }}</td>
                    <td class="der negrita">{{ $fmt($factura->total) }}</td>
                    <td class="der">{{ $fmt($factura->saldo()) }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="muted centro">No se emitieron facturas en el periodo.</td></tr>
            @endforelse
            <tr class="total-fila">
                <td colspan="6">TOTAL ({{ $resumen['cantidad'] }} facturas válidas)</td>
                <td class="der">{{ $fmt($resumen['subtotal']) }}</td>
                <td class="der">{{ $fmt($resumen['descuento']) }}</td>
                <td class="der">{{ $fmt($resumen['iva']) }}</td>
                <td class="der">{{ $fmt($resumen['total']) }}</td>
                <td class="der">{{ $fmt($resumen['saldo']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Cobros recibidos ({{ $pagos->count() }})</p>
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Factura</th><th>Cliente</th><th>Tipo de venta</th><th>Método</th><th>Comprobante</th><th class="der">Monto</th></tr>
        </thead>
        <tbody>
            @forelse ($pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $pago->factura->numero_factura }}</td>
                    <td>{{ $pago->factura->cliente->razon_social }}</td>
                    <td>{{ $pago->factura->tipo_transaccion->etiqueta() }}</td>
                    <td>{{ $pago->metodo_pago->etiqueta() }}</td>
                    <td>{{ $pago->comprobante_referencia ?? '—' }}</td>
                    <td class="der menta">+{{ $fmt($pago->monto_abonado) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted centro">No se registraron cobros en el periodo.</td></tr>
            @endforelse
            <tr class="total-fila"><td colspan="6">TOTAL COBRADO</td><td class="der">{{ $fmt($resumen['cobrado']) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
