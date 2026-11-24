{{-- Líneas y totales de un documento en PDF. Variables: $documento (Factura o Cotizacion con detalles), $fmt --}}
<table class="tabla">
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="der">Cant.</th>
            <th class="der">P. unitario</th>
            <th class="der">Desc.</th>
            <th class="der">Subtotal</th>
            <th class="der">IVA</th>
            <th class="der">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($documento->detalles as $detalle)
            <tr>
                <td>{{ $detalle->descripcion }}</td>
                <td class="der">{{ rtrim(rtrim($detalle->cantidad, '0'), '.') }}</td>
                <td class="der">{{ $fmt($detalle->precio_unitario) }}</td>
                <td class="der">{{ (float) $detalle->descuento > 0 ? '-'.$fmt($detalle->descuento).' ('.rtrim(rtrim($detalle->porcentaje_descuento, '0'), '.').'%)' : '—' }}</td>
                <td class="der">{{ $fmt($detalle->subtotal) }}</td>
                <td class="der">{{ $fmt($detalle->iva) }} ({{ (int) $detalle->porcentaje_iva }}%)</td>
                <td class="der negrita">{{ $fmt($detalle->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted centro">Documento sin líneas de detalle.</td></tr>
        @endforelse
    </tbody>
</table>

<table style="margin-top: 6px;">
    <tr>
        <td style="width: 58%;"></td>
        <td style="width: 42%;">
            <table class="tabla">
                <tr><td class="muted">Subtotal (base imponible)</td><td class="der">{{ $fmt($documento->subtotal) }}</td></tr>
                @if ((float) $documento->descuento > 0)
                    <tr><td class="muted">Descuentos aplicados</td><td class="der cobre">-{{ $fmt($documento->descuento) }}</td></tr>
                @endif
                <tr><td class="muted">IVA</td><td class="der">{{ $fmt($documento->iva) }}</td></tr>
                <tr class="total-fila"><td>TOTAL</td><td class="der">{{ $fmt($documento->total) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
