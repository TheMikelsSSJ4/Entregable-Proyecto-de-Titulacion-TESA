@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

@php
    $id ??= $name;
    $tieneError = $errors->has($name);
    $describe = $tieneError ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :name="$name" :id="$id" :hint="$hint" :required="$required" {{ $attributes->only('class') }}>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @if ($tieneError) aria-invalid="true" @endif
        @if ($describe) aria-describedby="{{ $describe }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'campo'.($tieneError ? ' campo-error' : '')]) }}
    >
</x-ui.field>
