{{-- Mensajes de resultado de la última acción (anunciados a lectores de pantalla). --}}
<div class="flex flex-col gap-3 empty:hidden">
    @if (session('success'))
        <div role="status" class="flex items-start gap-3 rounded-xl border border-menta/30 bg-menta/[0.08] px-4 py-3 text-sm text-platino">
            <x-ui.icon name="check" class="mt-0.5 size-4 text-menta" />
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-cobre/40 bg-cobre/[0.08] px-4 py-3 text-sm text-platino">
            <x-ui.icon name="alerta" class="mt-0.5 size-4 text-cobre" />
            <p>{{ session('error') }}</p>
        </div>
    @endif
    @if ($errors->any() && ! session('error'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-cobre/40 bg-cobre/[0.08] px-4 py-3 text-sm text-platino">
            <x-ui.icon name="alerta" class="mt-0.5 size-4 text-cobre" />
            <p>Revise los campos marcados: {{ $errors->count() === 1 ? 'hay 1 error' : 'hay '.$errors->count().' errores' }} en el formulario.</p>
        </div>
    @endif
</div>
