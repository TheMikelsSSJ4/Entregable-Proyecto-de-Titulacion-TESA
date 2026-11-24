<x-layouts.app title="Nueva cotización">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('documentos.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Documentos</a>
    </div>
    <form method="POST" action="{{ route('cotizaciones.store') }}" novalidate>
        @csrf
        @include('cotizaciones._form', ['textoBoton' => 'Emitir cotización', 'urlCancelar' => route('documentos.index')])
    </form>
</x-layouts.app>
