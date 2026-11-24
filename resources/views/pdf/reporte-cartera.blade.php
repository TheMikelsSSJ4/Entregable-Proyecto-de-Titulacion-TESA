<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de cartera</title>
    @include('pdf._estilos')
</head>
<body>
    @php $fmt = fn ($v) => '$'.number_format((float) $v, 2, '.', ','); @endphp

    <div class="pie">Reporte de cartera por cobrar · Facturas emitidas {{ \Illuminate\Support\Str::lcfirst($periodo) }} · Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombre_completo }}. Días de mora calculados al {{ today()->format('d/m/Y') }}.</div>

    @include('pdf._cabecera', [
        'tipoDocumento' => 'Reporte de cartera por cobrar',
        'numero' => 'Corte al '.today()->format('d/m/Y'),
        'detalle' => 'Facturas emitidas '.\Illuminate\Support\Str::lcfirst($periodo),
    ])

    <div class="espacio"></div>

    <table>
        <tr>
            @foreach ([
                ['Cartera por cobrar', $resumen['por_cobrar'], ''],
                ['Al día', $resumen['al_dia'], 'menta'],
                ['Por vencer ('.config('contable.dias_alerta_previa').' días)', $resumen['por_vencer_monto'], 'oro'],
                ['Total en mora', $resumen['en_mora'], 'cobre'],
            ] as [$etiqueta, $monto, $clase])
                <td style="width: 25%; padding: 0 {{ $loop->last ? '0' : '6px' }} 0 0;">
                    <div class="caja">
                        <p class="eyebrow">{{ $etiqueta }}</p>
                        <p class="negrita {{ $clase }}" style="font-size: 14px; margin-top: 3px;">{{ $fmt($monto) }}</p>
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
                        @foreach ($resumen['tramos'] as $tramo)
                            <tr><td>{{ $tramo['etiqueta'] }}</td><td class="der">{{ $tramo['cantidad'] }}</td><td class="der cobre">{{ $fmt($tramo['monto']) }}</td></tr>
                        @endforeach
                        <tr><td class="negrita">Índice de morosidad</td><td></td><td class="der negrita">{{ number_format($resumen['indice_morosidad'], 1) }}%</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 60%; vertical-align: top;">
                <p class="eyebrow" style="margin-bottom: 4px;">Principales deudores</p>
                <table class="tabla">
                    <thead><tr><th>Cliente</th><th class="der">Facturas</th><th class="der">Mora máx.</th><th class="der">Saldo</th></tr></thead>
                    <tbody>
                        @forelse ($resumen['top_deudores'] as $fila)
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

    <div class="espacio"></div>

    <p class="eyebrow" style="margin-bottom: 4px;">Detalle de facturas con saldo ({{ $facturas->count() }})</p>
    <table class="tabla">
        <thead>
            <tr>
                <th>Cliente</th><th>RUC / Cédula</th><th>Factura</th><th>Emisión</th><th>Vencimiento</th>
                <th class="der">Total</th><th class="der">Abonado</th><th class="der">Saldo</th><th class="der">Días mora</th><th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($facturas as $factura)
                @php $indicador = $factura->indicadorCartera(); @endphp
                <tr>
                    <td>{{ $factura->cliente->razon_social }}</td>
                    <td>{{ $factura->cliente->ruc_cedula }}</td>
                    <td>{{ $factura->numero_factura }}</td>
                    <td>{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                    <td class="{{ $factura->estaVencida() ? 'cobre' : '' }}">{{ $factura->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td class="der">{{ $fmt($factura->total) }}</td>
                    <td class="der menta">{{ $fmt($factura->totalAbonado()) }}</td>
                    <td class="der negrita">{{ $fmt($factura->saldo()) }}</td>
                    <td class="der {{ $factura->estaVencida() ? 'cobre negrita' : '' }}">{{ $factura->diasMora() ?: '—' }}</td>
                    <td><span class="chip chip-{{ $indicador['tono'] }}">{{ $indicador['etiqueta'] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="10" class="muted centro">No existen facturas con saldo pendiente.</td></tr>
            @endforelse
            <tr class="total-fila">
                <td colspan="7">TOTAL CARTERA POR COBRAR</td>
                <td class="der">{{ $fmt($resumen['por_cobrar']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
