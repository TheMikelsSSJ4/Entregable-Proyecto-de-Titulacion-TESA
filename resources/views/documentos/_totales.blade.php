{{-- Resumen de totales en vivo. Debe incluirse dentro de x-data="documentoComercial(...)". --}}
<dl class="flex flex-col gap-3 text-sm" aria-live="polite">
    <div class="flex justify-between gap-3"><dt class="text-niebla">Productos</dt><dd class="tnum" x-text="lineasValidas">0</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-niebla">Valor bruto</dt><dd class="tnum" x-text="dinero(totales.bruto)">$0.00</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-niebla">Descuentos</dt><dd class="tnum text-cobre" x-text="'− ' + dinero(totales.descuento)">− $0.00</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-niebla">Subtotal (base imponible)</dt><dd class="tnum font-medium" x-text="dinero(totales.subtotal)">$0.00</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-niebla">IVA</dt><dd class="tnum font-medium" x-text="dinero(totales.iva)">$0.00</dd></div>
    <div class="flex items-baseline justify-between gap-3 border-t border-tinta/[0.06] pt-3">
        <dt class="font-semibold">Total</dt>
        <dd class="tnum font-display text-2xl font-bold text-menta" x-text="dinero(totales.total)">$0.00</dd>
    </div>
</dl>
<p class="text-xs text-niebla">Vista previa. El sistema recalcula todo con los precios del catálogo al guardar.</p>
