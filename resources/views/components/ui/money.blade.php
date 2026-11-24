@props(['value' => 0, 'sign' => null])

@php
    $numero = (float) $value;
    [$entero, $centavos] = explode('.', number_format(abs($numero), 2, '.', ','));
    $prefijo = match (true) {
        $sign === '+' => '+',
        $sign === '-' || $numero < 0 => '−',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => 'tnum whitespace-nowrap']) }}>{{ $prefijo }}${{ $entero }}<span class="opacity-55">.{{ $centavos }}</span></span>
