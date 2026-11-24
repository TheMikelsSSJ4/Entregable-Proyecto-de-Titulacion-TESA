{{-- Campo de contraseña con botón "ojito": un clic la muestra y se queda visible; otro clic la oculta. --}}
@props([
    'label',
    'name',
    'id' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id ??= $name;
    $tieneError = $errors->has($name);
    $describe = $tieneError ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :name="$name" :id="$id" :hint="$hint" :required="$required" {{ $attributes->only('class') }}>
    <div x-data="{ show: false }" class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            x-bind:type="show ? 'text' : 'password'"
            @required($required)
            @if ($tieneError) aria-invalid="true" @endif
            @if ($describe) aria-describedby="{{ $describe }}" @endif
            {{ $attributes->except('class')->merge(['class' => 'campo pr-12'.($tieneError ? ' campo-error' : '')]) }}
        >
        <button type="button"
            x-on:click="show = !show"
            x-bind:aria-pressed="show.toString()"
            x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            aria-label="Mostrar contraseña"
            aria-controls="{{ $id }}"
            class="absolute inset-y-1 right-1 grid w-10 place-items-center rounded-lg text-niebla transition-colors hover:bg-tinta/[0.06] hover:text-platino">
            <x-ui.icon name="ojo" class="size-[1.1rem]" x-show="!show" />
            <x-ui.icon name="ojo-tachado" class="size-[1.1rem] text-menta" x-show="show" x-cloak />
        </button>
    </div>
</x-ui.field>
