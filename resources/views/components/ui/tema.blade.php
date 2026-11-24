{{--
    Botón para cambiar entre tema oscuro y claro. El tema inicial lo pinta el
    servidor (cookie privileg_tema), así la página no "parpadea" al cargar.
--}}
@php $esClaro = request()->cookie('privileg_tema') === 'claro'; @endphp

<button type="button" x-data="selectorTema" x-on:click="alternar()"
    aria-label="{{ $esClaro ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro' }}"
    x-bind:aria-label="claro ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro'"
    title="{{ $esClaro ? 'Tema oscuro' : 'Tema claro' }}"
    x-bind:title="claro ? 'Tema oscuro' : 'Tema claro'"
    data-selector-tema
    {{ $attributes->merge(['class' => 'grid size-10 shrink-0 place-items-center rounded-xl border border-tinta/10 text-niebla transition-colors hover:bg-tinta/[0.06] hover:text-platino']) }}>
    <x-ui.icon name="sol" class="size-[1.15rem] claro:hidden" />
    <x-ui.icon name="luna" class="hidden size-[1.15rem] claro:block" />
</button>
