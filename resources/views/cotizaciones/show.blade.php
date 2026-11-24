<x-layouts.app :title="'Cotización '.$cotizacion->numero" heading="Detalle de la cotización">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('documentos.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Documentos</a>
    </div>

    <section class="card flex flex-col gap-6 p-5 sm:p-6" aria-labelledby="titulo-cotizacion">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="eyebrow text-lila">Proforma comercial</p>
                <h2 id="titulo-cotizacion" class="mt-1 flex flex-wrap items-center gap-3 text-xl font-semibold">
                    <span class="tnum">{{ $cotizacion->numero }}</span>
                    <x-ui.badge :tone="$cotizacion->estado->tono()">{{ $cotizacion->estado->etiqueta() }}</x-ui.badge>
                </h2>
                <p class="mt-1 text-sm text-niebla">
                    <a href="{{ route('clientes.show', $cotizacion->cliente) }}" class="font-medium text-platino hover:text-menta">{{ $cotizacion->cliente->razon_social }}</a>
                    · <span class="tnum">{{ $cotizacion->cliente->ruc_cedula }}</span>
                    @foreach ($cotizacion->facturas as $facturaGenerada)
                        · facturada en <a href="{{ route('facturas.show', $facturaGenerada) }}" class="tnum text-menta hover:underline">{{ $facturaGenerada->numero_factura }}</a>
                    @endforeach
                </p>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                <x-ui.button :href="route('cotizaciones.pdf', ['cotizacion' => $cotizacion, 'imprimir' => 1])" icon="imprimir" variant="violet" size="sm" target="_blank" rel="noopener">Imprimir PDF</x-ui.button>
                <x-ui.button :href="route('cotizaciones.pdf', $cotizacion)" icon="descarga" variant="secondary" size="sm">Descargar PDF</x-ui.button>
                @if ($cotizacion->estado !== \App\Enums\EstadoCotizacion::Facturada)
                    @can('update', $cotizacion)
                        <x-ui.button :href="route('cotizaciones.edit', $cotizacion)" icon="editar" variant="secondary" size="sm">Editar</x-ui.button>
                    @endcan
                @endif
                @if ($cotizacion->estado->puedeFacturarse())
                    @can('create', \App\Models\Factura::class)
                        <x-ui.button :href="route('facturas.create', ['cotizacion' => $cotizacion->id])" icon="flujo" size="sm">Facturar</x-ui.button>
                    @endcan
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Total cotizado</dt>
                <dd class="mt-2 font-display text-2xl font-bold"><x-ui.money :value="$cotizacion->total" /></dd>
                <dd class="text-xs text-niebla">IVA incluido · <x-ui.money :value="$cotizacion->iva" /> de IVA</dd>
            </div>
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Emisión · validez</dt>
                <dd class="tnum mt-2 font-display text-lg font-semibold">{{ $cotizacion->fecha_emision->format('d/m/Y') }}</dd>
                <dd class="text-xs text-niebla">Válida hasta el {{ $cotizacion->fecha_emision->copy()->addDays((int) config('contable.validez_cotizacion_dias'))->format('d/m/Y') }}</dd>
            </div>
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Vendedor</dt>
                <dd class="mt-2 font-display text-lg font-semibold">{{ \Illuminate\Support\Str::upper($cotizacion->vendedor->nombre_completo) }}</dd>
                <dd class="text-xs text-niebla">Registrada por {{ $cotizacion->usuario->nombre_completo }}</dd>
            </div>
        </dl>
    </section>

    @include('documentos._detalle', ['documento' => $cotizacion])
</x-layouts.app>
