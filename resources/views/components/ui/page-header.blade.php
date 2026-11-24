@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'tone' => 'menta',
])

@php
    $tonos = ['menta' => 'text-menta', 'violeta' => 'text-lila', 'oro' => 'text-oro', 'cobre' => 'text-cobre', 'cian' => 'text-cian'];
@endphp

<div {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow {{ $tonos[$tone] ?? 'text-menta' }}">{{ $eyebrow }}</p>
        @endif
        <h2 class="mt-1 text-lg font-semibold text-platino sm:text-xl">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-niebla">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 sm:justify-end">{{ $actions }}</div>
    @endisset
</div>
