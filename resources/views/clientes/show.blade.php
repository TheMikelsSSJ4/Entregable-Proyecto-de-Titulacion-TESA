<x-layouts.app :title="$cliente->razon_social" heading="Detalle del cliente">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('clientes.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Clientes</a>
    </div>

    <section class="card flex flex-col gap-6 p-5 sm:p-6" aria-labelledby="titulo-cliente">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="eyebrow text-menta">{{ $cliente->tipoIdentificacion() }} · <span class="tnum">{{ $cliente->ruc_cedula }}</span></p>
                <h2 id="titulo-cliente" class="mt-1 text-xl font-semibold break-words">{{ $cliente->razon_social }}</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-3">
                    <div class="min-w-0"><dt class="text-xs text-niebla">Dirección</dt><dd class="break-words">{{ $cliente->direccion }}</dd></div>
                    <div><dt class="text-xs text-niebla">Teléfono</dt><dd class="tnum">{{ $cliente->telefono ?? '—' }}</dd></div>
                    <div class="min-w-0"><dt class="text-xs text-niebla">Correo</dt><dd class="break-all">{{ $cliente->correo ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                <x-ui.button :href="route('facturas.create', ['cliente' => $cliente->id])" icon="mas" size="sm">Nueva factura</x-ui.button>
                @can('create', \App\Models\Cotizacion::class)
                    <x-ui.button :href="route('cotizaciones.create', ['cliente' => $cliente->id])" icon="documento" variant="violet" size="sm">Cotizar</x-ui.button>
                @endcan
                @can('ver-reportes')
                    <x-ui.button :href="route('reportes.exportar', ['tipo' => 'estado-cuenta', 'formato' => 'pdf', 'cliente_id' => $cliente->id, 'fecha_inicio' => $primeraFactura ?? today()->startOfYear()->toDateString(), 'fecha_fin' => today()->toDateString()])" icon="descarga" variant="secondary" size="sm">Estado de cuenta PDF</x-ui.button>
                @endcan
                <x-ui.button :href="route('clientes.edit', $cliente)" icon="editar" variant="secondary" size="sm">Editar</x-ui.button>
                @can('delete', $cliente)
                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                          data-confirm="Se eliminará a {{ $cliente->razon_social }}. Sólo es posible si no tiene facturas ni cotizaciones registradas."
                          data-confirm-boton="Eliminar cliente">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" icon="basura" variant="danger" size="sm">Eliminar</x-ui.button>
                    </form>
                @endcan
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Saldo pendiente</dt>
                <dd class="mt-2 font-display text-2xl font-bold"><x-ui.money :value="$resumen['saldo']" /></dd>
            </div>
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Saldo en mora</dt>
                <dd class="mt-2 font-display text-2xl font-bold {{ (float) $resumen['en_mora'] > 0 ? 'text-cobre' : 'text-menta' }}"><x-ui.money :value="$resumen['en_mora']" /></dd>
            </div>
            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-4">
                <dt class="eyebrow text-niebla">Facturas abiertas</dt>
                <dd class="mt-2 font-display text-2xl font-bold"><span class="tnum">{{ $resumen['facturas_abiertas'] }}</span>
                    @if ($resumen['dias_mora_max'] > 0)
                        <span class="ml-1 align-middle"><x-ui.badge tone="cobre">{{ $resumen['dias_mora_max'] }} días de mora</x-ui.badge></span>
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section class="card min-w-0" aria-labelledby="titulo-facturas-cliente">
        <h2 id="titulo-facturas-cliente" class="px-5 pt-5 pb-3 text-base font-semibold">Historial de facturas
            @unless (auth()->user()->accedeAFinanzas())<span class="text-sm font-normal text-niebla">(registradas por usted)</span>@endunless
        </h2>
        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">N.º factura</th>
                        <th scope="col">Emisión</th>
                        <th scope="col">Vencimiento</th>
                        <th scope="col">Tipo</th>
                        <th scope="col" class="text-right">Total</th>
                        <th scope="col" class="text-right">Saldo</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facturas as $factura)
                        @php $indicador = $factura->indicadorCartera(); @endphp
                        <tr>
                            <td><a href="{{ route('facturas.show', $factura) }}" class="tnum font-medium hover:text-menta">{{ $factura->numero_factura }}</a></td>
                            <td class="tnum text-niebla">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                            <td class="tnum {{ $factura->estaVencida() ? 'text-cobre' : 'text-niebla' }}">{{ $factura->esCredito() ? $factura->fecha_vencimiento->format('d/m/Y') : '—' }}</td>
                            <td class="text-niebla">{{ $factura->tipo_transaccion->etiqueta() }}</td>
                            <td class="text-right"><x-ui.money :value="$factura->total" /></td>
                            <td class="text-right font-semibold"><x-ui.money :value="$factura->saldo()" /></td>
                            <td><x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="7" title="Sin facturas registradas">Este cliente todavía no tiene ventas registradas.</x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $facturas->links() }}</div>
    </section>

    @if ($cotizaciones->isNotEmpty())
        <section class="card p-5" aria-labelledby="titulo-cot-cliente">
            <h2 id="titulo-cot-cliente" class="text-base font-semibold">Cotizaciones recientes</h2>
            <ul class="mt-3 divide-y divide-tinta/[0.05]">
                @foreach ($cotizaciones as $cotizacion)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="tnum font-medium hover:text-menta">{{ $cotizacion->numero }}</a>
                        <span class="tnum text-sm text-niebla">{{ $cotizacion->fecha_emision->format('d/m/Y') }}</span>
                        <x-ui.money :value="$cotizacion->total" class="font-semibold" />
                        <x-ui.badge :tone="$cotizacion->estado->tono()">{{ $cotizacion->estado->etiqueta() }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
