@props(['icon' => 'documento', 'title', 'colspan' => null])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-12">
            <div class="flex flex-col items-center gap-2 text-center">
                <span class="grid size-11 place-items-center rounded-xl bg-tinta/[0.04] text-niebla"><x-ui.icon :name="$icon" /></span>
                <p class="font-medium text-platino">{{ $title }}</p>
                <div class="max-w-md text-sm text-niebla">{{ $slot }}</div>
            </div>
        </td>
    </tr>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 px-4 py-12 text-center']) }}>
        <span class="grid size-11 place-items-center rounded-xl bg-tinta/[0.04] text-niebla"><x-ui.icon :name="$icon" /></span>
        <p class="font-medium text-platino">{{ $title }}</p>
        <div class="max-w-md text-sm text-niebla">{{ $slot }}</div>
    </div>
@endif
