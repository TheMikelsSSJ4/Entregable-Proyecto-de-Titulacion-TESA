<x-layouts.app :title="'Editar '.$producto->codigo">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('productos.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Productos</a>
    </div>
    <form method="POST" action="{{ route('productos.update', $producto) }}" class="flex flex-col gap-6" novalidate>
        @csrf
        @method('PUT')
        <div>
            <p class="eyebrow text-cian">Catálogo de facturación</p>
            <h2 class="mt-1 text-lg font-semibold">Editar <span class="tnum">{{ $producto->codigo }}</span></h2>
            <p class="mt-1 text-sm text-niebla">Los cambios de precio no alteran las cotizaciones ni facturas ya emitidas.</p>
        </div>
        @include('productos._form')
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('productos.index')" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Guardar cambios</x-ui.button>
        </div>
    </form>
</x-layouts.app>
