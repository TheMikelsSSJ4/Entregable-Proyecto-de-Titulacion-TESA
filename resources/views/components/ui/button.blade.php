@props([
    'variant' => 'primary',
    'href' => null,
    'icon' => null,
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold whitespace-nowrap transition-[background-color,border-color,color,box-shadow,transform] duration-150 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2';

    $tamanos = [
        'sm' => 'px-3 py-1.5 text-xs min-h-8',
        'md' => 'px-4 py-2.5 text-sm min-h-10',
    ];

    $variantes = [
        'primary' => 'bg-menta text-obsidiana shadow-[0_0_24px_-6px_rgb(0_245_160/0.55)] hover:bg-menta-brillo focus-visible:outline-menta',
        'secondary' => 'border border-tinta/10 bg-tinta/[0.04] text-platino hover:border-tinta/20 hover:bg-tinta/[0.08] focus-visible:outline-platino',
        'violet' => 'bg-violeta text-white shadow-[0_0_24px_-8px_rgb(139_92_246/0.7)] hover:bg-violeta-brillo focus-visible:outline-violeta',
        'danger' => 'border border-cobre/40 bg-cobre/10 text-cobre hover:border-cobre/70 hover:bg-cobre/20 focus-visible:outline-cobre',
        'ghost' => 'text-niebla hover:bg-tinta/[0.06] hover:text-platino focus-visible:outline-platino',
    ];

    $clases = $base.' '.($tamanos[$size] ?? $tamanos['md']).' '.($variantes[$variant] ?? $variantes['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
