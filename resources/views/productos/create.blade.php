<x-layouts.app title="Nuevo producto">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('productos.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Productos</a>
    </div>
    <form method="POST" action="{{ route('productos.store') }}" class="flex flex-col gap-6" novalidate>
        @csrf
        <div>
            <p class="eyebrow text-cian">Catálogo de facturación</p>
            <h2 class="mt-1 text-lg font-semibold">Registrar producto o servicio</h2>
        </div>
        @include('productos._form')
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('productos.index')" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Registrar producto</x-ui.button>
        </div>
    </form>
</x-layouts.app>
