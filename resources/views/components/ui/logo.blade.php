@props(['compact' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-menta/60 bg-menta/[0.06] text-menta shadow-[0_0_20px_-6px_rgb(0_245_160/0.6)]">
        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 4L20 17H4L12 4Z" />
            <circle cx="12" cy="20.5" r="1.4" class="fill-violeta" stroke="none" />
        </svg>
    </span>
    @unless ($compact)
        <span class="flex min-w-0 flex-col leading-tight">
            <span class="font-display text-base font-bold tracking-wide text-platino">PRIVILEG<span class="text-menta">·</span></span>
            <span class="font-display text-[0.65rem] font-semibold tracking-[0.24em] text-lila">// CONTABLE</span>
        </span>
    @endunless
</span>
