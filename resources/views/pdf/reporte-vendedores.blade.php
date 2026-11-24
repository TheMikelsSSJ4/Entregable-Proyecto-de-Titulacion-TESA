<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas por vendedor</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Ventas por vendedor · {{ $periodo }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombre_completo }}. Excluye facturas anuladas. Cobrado y saldo corresponden a las facturas emitidas en el periodo.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Reporte de ventas por vendedor',
        'numero' => $periodo,
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            @foreach ([
                ['Total vendido', $totales['total'], '', $totales['cantidad'].' facturas'],
                ['Cobrado', $totales['cobrado'], 'menta', ''],
                ['Saldo pendiente', $totales['saldo'], '', ''],
                ['Saldo en mora', $totales['en_mora'], 'cobre', ''],
            ] as [$etiqueta, $monto, $clase, $nota])
                <td style="width: 25%; padding: 0 {{ $loop->last ? '0' : '6px' }} 0 0;">
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

    <p class="eyebrow" style="margin-bottom: 4px;">Resumen por vendedor ({{ count($filas) }})</p>
    <table class="tabla">
        <thead>
            <tr>
                <th>Vendedor</th><th>Rol</th><th class="der">Facturas</th><th class="der">Total vendido</th><th class="der">Contado</th>
                <th class="der">Crédito</th><th class="der">Cobrado</th><th class="der">Saldo</th><th class="der">En mora</th><th class="der">Particip.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    <td class="negrita">{{ \Illuminate\Support\Str::upper($fila['vendedor']->nombre_completo) }}</td>
                    <td class="muted">{{ $fila['vendedor']->rol->nombre->value }}</td>
                    <td class="der">{{ $fila['cantidad'] }}</td>
                    <td class="der negrita">{{ $fmt($fila['total']) }}</td>
                    <td class="der">{{ $fmt($fila['contado']) }}</td>
                    <td class="der">{{ $fmt($fila['credito']) }}</td>
                    <td class="der menta">{{ $fmt($fila['cobrado']) }}</td>
                    <td class="der">{{ $fmt($fila['saldo']) }}</td>
                    <td class="der {{ (float) $fila['en_mora'] > 0 ? 'cobre negrita' : '' }}">{{ $fmt($fila['en_mora']) }}</td>
                    <td class="der">{{ number_format($fila['participacion'], 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="10" class="muted centro">No hay ventas registradas en el periodo.</td></tr>
            @endforelse
            <tr class="total-fila">
                <td colspan="2">TOTAL</td>
                <td class="der">{{ $totales['cantidad'] }}</td>
                <td class="der">{{ $fmt($totales['total']) }}</td>
                <td class="der">{{ $fmt($totales['contado']) }}</td>
                <td class="der">{{ $fmt($totales['credito']) }}</td>
                <td class="der">{{ $fmt($totales['cobrado']) }}</td>
                <td class="der">{{ $fmt($totales['saldo']) }}</td>
                <td class="der">{{ $fmt($totales['en_mora']) }}</td>
                <td class="der">{{ $totales['cantidad'] ? '100%' : '—' }}</td>
            </tr>
        </tbody>
    </table>

    @foreach (collect($facturas)->groupBy('vendedor_id') as $grupo)
        <div class="espacio"></div>
        <p class="eyebrow" style="margin-bottom: 4px;">Vendedor: {{ \Illuminate\Support\Str::upper($grupo->first()->vendedor->nombre_completo) }} · {{ $grupo->count() }} {{ $grupo->count() === 1 ? 'factura' : 'facturas' }}</p>
        <table class="tabla">
            <thead>
                <tr><th>Factura</th><th>Emisión</th><th>Cliente</th><th>Tipo</th><th>Estado</th><th class="der">Total</th><th class="der">Abonado</th><th class="der">Saldo</th></tr>
            </thead>
            <tbody>
                @foreach ($grupo as $factura)
                    <tr>
                        <td>{{ $factura->numero_factura }}</td>
                        <td>{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                        <td>{{ $factura->cliente->razon_social }}</td>
                        <td>{{ $factura->tipo_transaccion->etiqueta() }}</td>
                        <td>{{ $factura->indicadorCartera()['etiqueta'] }}</td>
                        <td class="der">{{ $fmt($factura->total) }}</td>
                        <td class="der menta">{{ $fmt($factura->totalAbonado()) }}</td>
                        <td class="der negrita">{{ $fmt($factura->saldo()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
