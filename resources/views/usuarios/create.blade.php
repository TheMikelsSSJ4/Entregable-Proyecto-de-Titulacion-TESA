<x-layouts.app title="Nuevo usuario">
    <form method="POST" action="{{ route('usuarios.store') }}" class="card flex flex-col gap-6 p-5 sm:p-6" novalidate>
        @csrf
        <div>
            <p class="eyebrow text-lila">Seguridad y Usuarios</p>
            <h2 class="mt-1 text-lg font-semibold">Registrar nuevo usuario</h2>
        </div>
        @include('usuarios._form')
        <div class="flex flex-col-reverse gap-2 border-t border-tinta/[0.06] pt-5 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('usuarios.index')" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Crear usuario</x-ui.button>
        </div>
    </form>
</x-layouts.app>
