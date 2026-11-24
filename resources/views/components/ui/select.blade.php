{{-- $options: [valor => etiqueta]. Para opciones con atributos extra use el slot. --}}
@props([
    'label',
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

@php
    $id ??= $name;
    $tieneError = $errors->has($name);
    $seleccionado = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
    $describe = $tieneError ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :name="$name" :id="$id" :hint="$hint" :required="$required" {{ $attributes->only('class') }}>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @required($required)
        @if ($tieneError) aria-invalid="true" @endif
        @if ($describe) aria-describedby="{{ $describe }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'campo pr-9'.($tieneError ? ' campo-error' : '')]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected($seleccionado === (string) $valor)>{{ $etiqueta }}</option>
        @endforeach
        {{ $slot }}
    </select>
</x-ui.field>
