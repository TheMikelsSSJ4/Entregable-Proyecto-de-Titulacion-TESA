<x-layouts.app title="Transacciones" heading="Registro de transacciones (ventas y abonos)">
    <x-ui.page-header eyebrow="Control de operaciones financieras"
        title="Ventas al contado, a crédito y abonos"
        description="Registre ventas y aplique pagos parciales o totales contra facturas a crédito. El saldo se calcula automáticamente.">
        <x-slot:actions>
            <x-ui.button :href="route('facturas.create')" icon="mas" variant="violet">Nueva venta</x-ui.button>
            @can('create', \App\Models\PagoAbono::class)
                <x-ui.button :href="route('abonos.create')" icon="billete">Registrar abono</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <section class="card min-w-0" aria-labelledby="titulo-transacciones">
        <div class="flex flex-col gap-4 px-5 pt-5 pb-3">
            <h2 id="titulo-transacciones" class="text-base font-semibold">Historial de transacciones <span class="tnum text-niebla">({{ $facturas->total() }})</span>
                @unless (auth()->user()->accedeAFinanzas())<span class="text-sm font-normal text-niebla">· registradas por usted</span>@endunless
            </h2>
            <form method="GET" action="{{ route('facturas.index') }}" role="search" class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ $vendedores ? 'lg:grid-cols-[1fr_14rem_11rem_11rem_auto]' : 'lg:grid-cols-[1fr_12rem_12rem_auto]' }}">
                <div class="relative">
                    <label for="buscar" class="sr-only">Buscar por número de factura o cliente</label>
                    <x-ui.icon name="buscar" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-niebla" />
                    <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="N.º de factura o cliente" class="campo pl-9" maxlength="100">
                </div>
                @if ($vendedores)
                    <div>
                        <label for="vendedor" class="sr-only">Vendedor</label>
                        <select id="vendedor" name="vendedor" class="campo" data-autoenvio>
                            <option value="">Todos los vendedores</option>
                            @foreach ($vendedores as $id => $nombre)
                                <option value="{{ $id }}" @selected((string) ($filtros['vendedor'] ?? '') === (string) $id)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="tipo" class="sr-only">Tipo de transacción</label>
                    <select id="tipo" name="tipo" class="campo" data-autoenvio>
                        <option value="">Todos los tipos</option>
                        @foreach (\App\Enums\TipoTransaccion::cases() as $tipo)
                            <option value="{{ $tipo->value }}" @selected(($filtros['tipo'] ?? '') === $tipo->value)>{{ $tipo->etiqueta() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado" class="sr-only">Estado</label>
                    <select id="estado" name="estado" class="campo" data-autoenvio>
                        <option value="">Todos los estados</option>
                        @foreach (\App\Enums\EstadoFactura::cases() as $estado)
                            <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">N.º factura</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Vendedor</th>
                        <th scope="col">Emisión</th>
                        <th scope="col">Tipo</th>
                        <th scope="col" class="text-right">Total</th>
                        <th scope="col" class="text-right">Saldo</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facturas as $factura)
                        @php $indicador = $factura->indicadorCartera(); @endphp
                        <tr>
                            <td><a href="{{ route('facturas.show', $factura) }}" class="tnum font-medium hover:text-menta">{{ $factura->numero_factura }}</a></td>
                            <td class="max-w-64"><span class="block truncate font-semibold" title="{{ $factura->cliente->razon_social }}">{{ $factura->cliente->razon_social }}</span></td>
                            <td class="max-w-48"><span class="block truncate text-niebla" title="{{ $factura->vendedor->nombre_completo }}">{{ $factura->vendedor->nombre_completo }}</span></td>
                            <td class="tnum text-niebla">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                            <td class="text-niebla">
                                {{ $factura->tipo_transaccion->etiqueta() }}
                                @if ($factura->esCredito() && $factura->estado === \App\Enums\EstadoFactura::Parcial)<span class="text-xs">(abono)</span>@endif
                            </td>
                            <td class="text-right"><x-ui.money :value="$factura->total" /></td>
                            <td class="text-right font-semibold {{ (float) $factura->saldo() > 0 ? '' : 'text-niebla' }}"><x-ui.money :value="$factura->saldo()" /></td>
                            <td><x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge></td>
                            <td class="text-right">
                                <x-ui.button :href="route('facturas.show', $factura)" variant="ghost" size="sm" icon="ojo" aria-label="Ver factura {{ $factura->numero_factura }}">Ver</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="9" icon="flujo" title="No hay transacciones para mostrar">
                            @if (array_filter($filtros))
                                Ninguna factura coincide con los filtros. <a href="{{ route('facturas.index') }}" class="text-menta underline underline-offset-4">Quitar filtros</a>
                            @else
                                Registre la primera venta con «Nueva venta».
                            @endif
                        </x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $facturas->links() }}</div>
    </section>
</x-layouts.app>
