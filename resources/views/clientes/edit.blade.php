<x-layouts.app title="Editar cliente">
    <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="card flex flex-col gap-6 p-5 sm:p-6" novalidate>
        @csrf
        @method('PUT')
        <div>
            <p class="eyebrow text-menta">Módulo de clientes</p>
            <h2 class="mt-1 text-lg font-semibold">Editar {{ $cliente->razon_social }}</h2>
        </div>
        @include('clientes._form')
        <div class="flex flex-col-reverse gap-2 border-t border-tinta/[0.06] pt-5 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('clientes.show', $cliente)" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Guardar cambios</x-ui.button>
        </div>
    </form>
</x-layouts.app>
