@props(['tone' => 'niebla', 'dot' => true])

@php
    $tonos = [
        'menta' => 'border-menta/35 bg-menta/10 text-menta',
        'cobre' => 'border-cobre/40 bg-cobre/10 text-cobre',
        'oro' => 'border-oro/35 bg-oro/10 text-oro',
        'cian' => 'border-cian/35 bg-cian/10 text-cian',
        'violeta' => 'border-violeta/40 bg-violeta/10 text-lila',
        'niebla' => 'border-tinta/10 bg-tinta/[0.04] text-niebla',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 font-display text-[0.68rem] font-semibold tracking-[0.12em] whitespace-nowrap uppercase '.($tonos[$tone] ?? $tonos['niebla'])]) }}>
    @if ($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
