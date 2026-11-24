{{-- Formulario de producto con precio final calculado en vivo (Alpine.js). Requiere $producto. --}}
@php
    $inicial = [
        'precio_base' => old('precio_base', $producto->precio_base),
        'porcentaje_descuento' => old('porcentaje_descuento', $producto->porcentaje_descuento ?? '0'),
        'porcentaje_iva' => (string) old('porcentaje_iva', (int) $producto->porcentaje_iva),
    ];
@endphp

<div x-data="calculadoraProducto(@js($inicial))" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_20rem]">
    <div class="card flex flex-col gap-6 p-5 sm:p-6">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-[12rem_1fr]">
            <x-ui.input label="Código" name="codigo" :value="$producto->codigo" required maxlength="30" autocomplete="off" class="tnum"
                placeholder="ABR-001" hint="Letras, números y guiones." />
            <x-ui.input label="Nombre del producto o servicio" name="nombre" :value="$producto->nombre" required maxlength="200" />
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <x-ui.input label="Precio base (sin IVA)" name="precio_base" type="number" step="0.01" min="0.01" inputmode="decimal" required class="tnum"
                placeholder="0.00" x-model="precioBase" />
            <x-ui.input label="% de descuento" name="porcentaje_descuento" type="number" step="0.01" min="0" max="100" inputmode="decimal" required class="tnum"
                x-model="descuento" hint="0 si no tiene descuento." />
            <x-ui.select label="% de IVA" name="porcentaje_iva" required x-model="iva"
                :value="$inicial['porcentaje_iva']"
                :options="collect(config('contable.tarifas_iva'))->mapWithKeys(fn ($t) => [$t => 'IVA '.$t.'%'])->all()" />
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="mt-0.5 size-4 accent-menta" @checked(old('activo', $producto->activo ?? true))>
            <span>
                <span class="block text-sm font-medium">Producto activo</span>
                <span class="block text-xs text-niebla">Sólo los productos activos aparecen al cotizar y facturar. Desactive en lugar de eliminar si ya fue vendido.</span>
            </span>
        </label>
    </div>

    <aside class="card flex flex-col gap-4 self-start p-5" aria-labelledby="titulo-precio">
        <p id="titulo-precio" class="eyebrow text-cian">Precio de venta</p>
        <dl class="flex flex-col gap-3 text-sm" aria-live="polite">
            <div class="flex justify-between gap-3"><dt class="text-niebla">Precio base</dt><dd class="tnum" x-text="dinero(montos.bruto)">$0.00</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-niebla">Descuento</dt><dd class="tnum text-cobre" x-text="'− ' + dinero(montos.descuento)">− $0.00</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-niebla">Base imponible</dt><dd class="tnum" x-text="dinero(montos.subtotal)">$0.00</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-niebla">IVA <span x-text="iva + '%'"></span></dt><dd class="tnum" x-text="dinero(montos.iva)">$0.00</dd></div>
            <div class="flex items-baseline justify-between gap-3 border-t border-tinta/[0.06] pt-3">
                <dt class="font-semibold">Precio final</dt>
                <dd class="tnum font-display text-2xl font-bold text-menta" x-text="dinero(montos.total)">$0.00</dd>
            </div>
        </dl>
        <p class="text-xs text-niebla">El sistema recalcula y guarda el precio final al registrar.</p>
        {{ $slot ?? '' }}
    </aside>
</div>
