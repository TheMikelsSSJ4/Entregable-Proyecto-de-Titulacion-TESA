{{--
    Paginación del sistema: resumen "Mostrando 1–10 de 57", selector de filas por
    página (10, 20, 50, 100) y enlaces numerados. El selector aparece en todo listado
    con registros, aunque quepan en una sola página (p. ej. Usuarios).
--}}
@php
    $opcionesFilas = config('contable.filas_por_pagina');
    $conTotal = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $mostrarSelector = $conTotal && $paginator->total() > 0;
@endphp

@if ($paginator->hasPages() || $mostrarSelector)
    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
            @if ($conTotal)
                <p class="text-xs text-niebla">
                    Mostrando <span class="tnum font-semibold text-platino">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                    de <span class="tnum font-semibold text-platino">{{ $paginator->total() }}</span>
                </p>
            @endif

            @if ($mostrarSelector)
                <label class="flex items-center gap-2 text-xs text-niebla">
                    <span>Filas por página</span>
                    <select x-data x-on:change="window.location.href = $event.target.value"
                        class="campo tnum w-auto py-1.5 pr-8 pl-2.5 text-xs" data-filas-por-pagina>
                        @foreach ($opcionesFilas as $opcion)
                            <option value="{{ request()->fullUrlWithQuery(['por_pagina' => $opcion, $paginator->getPageName() => null]) }}" @selected($opcion === $paginator->perPage())>{{ $opcion }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>

        @if ($paginator->hasPages())
            <ul class="flex flex-wrap items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="grid size-9 place-items-center rounded-lg text-niebla/40" aria-disabled="true" aria-label="Página anterior"><x-ui.icon name="atras" class="size-4" /></span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-9 place-items-center rounded-lg text-niebla hover:bg-tinta/[0.06] hover:text-platino" aria-label="Página anterior"><x-ui.icon name="atras" class="size-4" /></a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="grid size-9 place-items-center text-niebla">…</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="tnum grid size-9 place-items-center rounded-lg bg-menta/15 text-sm font-semibold text-menta">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="tnum grid size-9 place-items-center rounded-lg text-sm text-niebla hover:bg-tinta/[0.06] hover:text-platino" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-9 place-items-center rounded-lg text-niebla hover:bg-tinta/[0.06] hover:text-platino" aria-label="Página siguiente"><x-ui.icon name="flecha" class="size-4" /></a>
                    @else
                        <span class="grid size-9 place-items-center rounded-lg text-niebla/40" aria-disabled="true" aria-label="Página siguiente"><x-ui.icon name="flecha" class="size-4" /></span>
                    @endif
                </li>
            </ul>
        @endif
    </nav>
@endif
