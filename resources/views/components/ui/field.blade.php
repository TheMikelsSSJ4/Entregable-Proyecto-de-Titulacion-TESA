{{-- Contenedor de campo de formulario: etiqueta visible, ayuda y error accesibles. --}}
@props([
    'label',
    'name',
    'id' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id ??= $name;
    $clave = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($clave);
@endphp

<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-col gap-1.5']) }}>
    <label for="{{ $id }}" class="text-sm font-medium text-platino/90">
        {{ $label }}
        @if ($required)<span class="text-cobre" aria-hidden="true">*</span>@endif
    </label>

    {{ $slot }}

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-start gap-1.5 text-xs text-cobre" role="alert">
            <x-ui.icon name="alerta" class="mt-px size-3.5" />{{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-niebla">{{ $hint }}</p>
    @endif
</div>
