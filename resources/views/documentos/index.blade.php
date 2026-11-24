<x-layouts.app title="Documentos" heading="Gestión y emisión de documentos (PDF)">
    <x-ui.page-header eyebrow="RF-03 · Generación de PDF" tone="cian"
        title="Comprobantes y cotizaciones"
        description="Historial de facturas y proformas emitidas, con descarga en PDF para imprimir o enviar al cliente.">
        <x-slot:actions>
            @if ($puedeCotizar)
                <x-ui.button :href="route('cotizaciones.create')" icon="mas" variant="violet">Nueva cotización</x-ui.button>
            @endif
            <x-ui.button :href="route('facturas.create')" icon="mas">Nueva factura</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="card min-w-0" aria-labelledby="titulo-documentos">
        <div class="flex flex-col gap-4 px-5 pt-5 pb-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 id="titulo-documentos" class="text-base font-semibold">Historial de documentos emitidos <span class="tnum text-niebla">({{ $documentos->total() }})</span></h2>
            <form method="GET" action="{{ route('documentos.index') }}" role="search" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_11rem_auto] lg:w-[36rem]">
                <div class="relative">
                    <label for="buscar" class="sr-only">Buscar por número o cliente</label>
                    <x-ui.icon name="buscar" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-niebla" />
                    <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="N.º de documento o cliente" class="campo pl-9" maxlength="100">
                </div>
                <div>
                    <label for="tipo" class="sr-only">Tipo de documento</label>
                    <select id="tipo" name="tipo" class="campo" data-autoenvio>
                        <option value="">Todos</option>
                        <option value="facturas" @selected(($filtros['tipo'] ?? '') === 'facturas')>Facturas</option>
                        <option value="cotizaciones" @selected(($filtros['tipo'] ?? '') === 'cotizaciones')>Cotizaciones</option>
                    </select>
                </div>
                <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Documento</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Fecha</th>
                        <th scope="col" class="text-right">Total</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documentos as $documento)
                        @php
                            $esFactura = $documento->tipo_documento === 'factura';
                            $estado = $esFactura ? \App\Enums\EstadoFactura::from($documento->estado) : \App\Enums\EstadoCotizacion::from($documento->estado);
                            $rutaVer = $esFactura ? route('facturas.show', $documento->id) : route('cotizaciones.show', $documento->id);
                            $rutaPdf = $esFactura ? route('facturas.pdf', $documento->id) : route('cotizaciones.pdf', $documento->id);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ $rutaVer }}" class="flex flex-col hover:text-menta">
                                    <span class="text-xs text-niebla">{{ $esFactura ? 'Factura · '.\App\Enums\TipoTransaccion::from($documento->tipo_transaccion)->etiqueta() : 'Cotización' }}</span>
                                    <span class="tnum font-semibold">{{ $documento->numero }}</span>
                                </a>
                            </td>
                            <td class="max-w-64"><span class="block truncate" title="{{ $documento->cliente }}">{{ $documento->cliente }}</span></td>
                            <td class="tnum text-niebla">{{ \Illuminate\Support\Carbon::parse($documento->fecha)->format('d/m/Y') }}</td>
                            <td class="text-right font-semibold"><x-ui.money :value="$documento->total" /></td>
                            <td><x-ui.badge :tone="$estado->tono()">{{ $estado->etiqueta() }}</x-ui.badge></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <x-ui.button :href="$rutaPdf.'?imprimir=1'" size="sm" variant="ghost" icon="imprimir" target="_blank" rel="noopener" aria-label="Imprimir PDF de {{ $documento->numero }}"><span class="sr-only">Imprimir</span></x-ui.button>
                                    <x-ui.button :href="$rutaPdf" size="sm" :variant="$esFactura ? 'danger' : 'secondary'" icon="descarga" aria-label="Descargar PDF de {{ $documento->numero }}">PDF</x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="6" icon="documento" title="No hay documentos para mostrar">
                            @if (array_filter($filtros))
                                Ningún documento coincide con la búsqueda. <a href="{{ route('documentos.index') }}" class="text-menta underline underline-offset-4">Quitar filtros</a>
                            @else
                                Las facturas y cotizaciones emitidas aparecerán aquí.
                            @endif
                        </x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $documentos->links() }}</div>
    </section>
</x-layouts.app>
