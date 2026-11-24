<x-layouts.app title="Editar usuario">
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="card flex flex-col gap-6 p-5 sm:p-6" novalidate>
        @csrf
        @method('PUT')
        <div>
            <p class="eyebrow text-lila">Seguridad y Usuarios</p>
            <h2 class="mt-1 text-lg font-semibold">Editar a {{ $usuario->nombre_completo }}</h2>
            @if ($usuario->is(auth()->user()))
                <p class="mt-1 text-sm text-oro">Está editando su propia cuenta: no puede cambiar su rol ni desactivarse.</p>
            @endif
        </div>
        @include('usuarios._form')
        <div class="flex flex-col-reverse gap-2 border-t border-tinta/[0.06] pt-5 sm:flex-row sm:justify-end">
            <x-ui.button :href="route('usuarios.index')" variant="secondary">Cancelar</x-ui.button>
            <x-ui.button type="submit" icon="check" data-enviando="Guardando…">Guardar cambios</x-ui.button>
        </div>
    </form>
</x-layouts.app>
