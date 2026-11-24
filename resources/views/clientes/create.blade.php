<x-layouts.app title="Nuevo cliente">
    <form method="POST" action="{{ route('clientes.store') }}" class="card flex flex-col gap-6 p-5 sm:p-6" novalidate>
        @csrf
        <div>
            <p class="eyebrow text-menta">Módulo de clientes</p>
            <h2 class="mt-1 text-lg font-semibold">Registrar nuevo cliente</h2>
        </div>
        @include('clientes._form')
        <div class="flex flex-col-reverse gap-2 border-t border-tinta/[0.06] pt-5 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('clientes.index')" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Registrar cliente</x-ui.button>
        </div>
    </form>
</x-layouts.app>
