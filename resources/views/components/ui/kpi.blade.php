{{-- Tarjeta de indicador del "Radar Contable". --}}
@props([
    'label',
    'tone' => 'platino',
    'icon' => null,
    'hint' => null,
])

@php
    $acentos = [
        'menta' => ['text-menta', 'bg-menta/10 text-menta', 'from-menta/[0.07]'],
        'cobre' => ['text-cobre', 'bg-cobre/10 text-cobre', 'from-cobre/[0.08]'],
        'violeta' => ['text-lila', 'bg-violeta/15 text-lila', 'from-violeta/[0.08]'],
        'cian' => ['text-cian', 'bg-cian/10 text-cian', 'from-cian/[0.06]'],
        'oro' => ['text-oro', 'bg-oro/10 text-oro', 'from-oro/[0.06]'],
        'platino' => ['text-platino', 'bg-tinta/[0.06] text-platino', 'from-tinta/[0.03]'],
    ];
    [$texto, $chip, $brillo] = $acentos[$tone] ?? $acentos['platino'];
@endphp

<section {{ $attributes->merge(['class' => "card relative flex min-w-0 flex-col gap-3 overflow-hidden bg-gradient-to-br {$brillo} to-transparent p-5"]) }}>
    <div class="flex items-start justify-between gap-3">
        <h2 class="eyebrow text-niebla">{{ $label }}</h2>
        @if ($icon)
            <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $chip }}">
                <x-ui.icon :name="$icon" class="size-4" />
            </span>
        @endif
    </div>
    <p class="font-display text-[1.7rem] leading-none font-bold tracking-tight {{ $texto }}">{{ $slot }}</p>
    @if ($hint || isset($pie))
        <div class="text-xs text-niebla">{{ $pie ?? $hint }}</div>
    @endif
</section>
