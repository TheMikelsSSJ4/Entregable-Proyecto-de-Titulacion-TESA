<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte general del periodo</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Reporte general · {{ $periodo }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombre_completo }}. Excluye facturas anuladas. Cartera y mora de las facturas emitidas en el periodo, calculadas al {{ today()->format('d/m/Y') }}. Documento interno; no reemplaza comprobantes del SRI.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Reporte general del periodo',
        'numero' => $periodo,
        'detalle' => 'Ventas, vendedores y cartera por cobrar',
    ])

    <div class="espacio"></div>

    {{-- 1. Ventas y cobros --}}
    <p class="eyebrow" style="margin-bottom: 4px;">1 · Ventas y cobros</p>
    <table>
        <tr>
            @foreach ([
                ['Total vendido', $resumen['total'], '', $resumen['cantidad'].' facturas'.($resumen['anuladas'] ? ' · '.$resumen['anuladas'].' anuladas' : '')],
                ['Al contado', $resumen['contado'], 'menta', ''],
                ['A crédito', $resumen['credito'], 'violeta', ''],
                ['Cobrado en el periodo', $resumen['cobrado'], 'menta', $cantidad_pagos.' pagos y abonos'],
                ['Saldo de las ventas', $resumen['saldo'], 'cobre', 'Pendiente de cobro'],
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
    <p class="muted" style="margin-top: 5px;">
        Subtotal {{ $fmt($resumen['subtotal']) }} · Descuentos {{ $fmt($resumen['descuento']) }} · IVA {{ $fmt($resumen['iva']) }}
    </p>

    <div class="espacio"></div>

    {{-- 2. Ventas por vendedor --}}
    <p class="eyebrow" style="margin-bottom: 4px;">2 · Ventas por vendedor ({{ count($vendedores) }})</p>
    <table class="tabla">
        <thead>
            <tr>
                <th>Vendedor</th><th class="der">Facturas</th><th class="der">Total vendido</th><th class="der">Contado</th>
                <th class="der">Crédito</th><th class="der">Cobrado</th><th class="der">Saldo</th><th class="der">En mora</th><th class="der">Particip.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendedores as $fila)
                <tr>
                    <td class="negrita">{{ \Illuminate\Support\Str::upper($fila['vendedor']->nombre_completo) }}</td>
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
                <tr><td colspan="9" class="muted centro">No hay ventas en el periodo.</td></tr>
            @endforelse
            <tr class="total-fila">
                <td>TOTAL</td>
                <td class="der">{{ $totales_vendedores['cantidad'] }}</td>
                <td class="der">{{ $fmt($totales_vendedores['total']) }}</td>
                <td class="der">{{ $fmt($totales_vendedores['contado']) }}</td>
                <td class="der">{{ $fmt($totales_vendedores['credito']) }}</td>
                <td class="der">{{ $fmt($totales_vendedores['cobrado']) }}</td>
                <td class="der">{{ $fmt($totales_vendedores['saldo']) }}</td>
                <td class="der">{{ $fmt($totales_vendedores['en_mora']) }}</td>
                <td class="der">{{ $totales_vendedores['cantidad'] > 0 ? '100.0%' : '0.0%' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="espacio"></div>

    {{-- 3. Cartera por cobrar --}}
    <p class="eyebrow" style="margin-bottom: 4px;">3 · Cartera por cobrar (corte al {{ today()->format('d/m/Y') }})</p>
    <table>
        <tr>
            @foreach ([
                ['Cartera por cobrar', $cartera['por_cobrar'], '', $cartera['facturas_abiertas'].' facturas abiertas'],
                ['Al día', $cartera['al_dia'], 'menta', ''],
                ['Por vencer ('.config('contable.dias_alerta_previa').' días)', $cartera['por_vencer_monto'], 'oro', $cartera['facturas_por_vencer'].' facturas'],
                ['En mora', $cartera['en_mora'], 'cobre', $cartera['facturas_vencidas'].' facturas · morosidad '.number_format($cartera['indice_morosidad'], 1).'%'],
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

    <table>
        <tr>
            <td style="width: 40%; padding-right: 8px; vertical-align: top;">
                <p class="eyebrow" style="margin-bottom: 4px;">Antigüedad de saldos vencidos</p>
                <table class="tabla">
                    <thead><tr><th>Tramo</th><th class="der">Facturas</th><th class="der">Saldo</th></tr></thead>
                    <tbody>
                        @foreach ($cartera['tramos'] as $tramo)
                            <tr><td>{{ $tramo['etiqueta'] }}</td><td class="der">{{ $tramo['cantidad'] }}</td><td class="der cobre">{{ $fmt($tramo['monto']) }}</td></tr>
                        @endforeach
                        <tr><td class="negrita">Índice de morosidad</td><td></td><td class="der negrita">{{ number_format($cartera['indice_morosidad'], 1) }}%</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 60%; vertical-align: top;">
                <p class="eyebrow" style="margin-bottom: 4px;">Principales deudores</p>
                <table class="tabla">
                    <thead><tr><th>Cliente</th><th class="der">Facturas</th><th class="der">Mora máx.</th><th class="der">Saldo</th></tr></thead>
                    <tbody>
                        @forelse ($cartera['top_deudores'] as $fila)
                            <tr>
                                <td>{{ $fila['cliente']->razon_social }}</td>
                                <td class="der">{{ $fila['facturas'] }}</td>
                                <td class="der {{ $fila['dias_mora'] > 0 ? 'cobre' : 'menta' }}">{{ $fila['dias_mora'] > 0 ? $fila['dias_mora'].' días' : 'Al día' }}</td>
                                <td class="der negrita">{{ $fmt($fila['saldo']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted centro">Sin saldos pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
