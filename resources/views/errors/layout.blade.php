<x-layouts.guest :title="$titulo">
    <div class="card flex w-full max-w-lg flex-col items-center gap-4 p-8 text-center">
        <x-ui.logo />
        <p class="mt-4 font-display text-6xl font-bold {{ $tono ?? 'text-cobre' }}">{{ $codigo }}</p>
        <h1 class="font-display text-xl font-semibold">{{ $titulo }}</h1>
        <p class="text-sm text-niebla">{{ $mensaje }}</p>
        <div class="mt-2 flex flex-wrap justify-center gap-2">
            <x-ui.button :href="url()->previous() !== url()->current() ? url()->previous() : url('/')" variant="secondary" icon="atras">Volver</x-ui.button>
            <x-ui.button :href="url('/')" icon="panel">Ir al Dashboard</x-ui.button>
        </div>
    </div>
</x-layouts.guest>
