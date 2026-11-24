<x-layouts.app :title="'Editar '.$cotizacion->numero">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />{{ $cotizacion->numero }}</a>
    </div>
    <form method="POST" action="{{ route('cotizaciones.update', $cotizacion) }}" novalidate>
        @csrf
        @method('PUT')
        @include('cotizaciones._form', ['textoBoton' => 'Guardar cambios', 'urlCancelar' => route('cotizaciones.show', $cotizacion)])
    </form>
</x-layouts.app>
