<x-layouts.app title="Cartera y Alertas" heading="Control de cartera y alertas de vencimiento">
    @php
        $condiciones = [
            '' => 'Todas',
            'vencidas' => 'Vencidas',
            'por_vencer' => 'Por vencer',
            'al_dia' => 'Al día',
        ];
        $condicionActual = (string) ($filtros['condicion'] ?? '');
        $maxTramo = max(1, ...array_map(fn ($t) => (float) $t['monto'], $resumen['tramos']));
    @endphp

    <section class="card flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between" aria-labelledby="titulo-monitoreo">
        <div class="min-w-0">
            <p class="eyebrow text-cobre">RF-05 · Monitoreo de morosidad</p>
            <h2 id="titulo-monitoreo" class="mt-1 text-lg font-semibold sm:text-xl">Saldos pendientes y días de retraso</h2>
            <p class="mt-1 max-w-xl text-sm text-niebla">Los días de mora se calculan automáticamente comparando la fecha de vencimiento de cada factura con la fecha actual ({{ now()->format('d/m/Y') }}).</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button :href="route('reportes.exportar', ['tipo' => 'cartera', 'formato' => 'pdf', 'fecha_inicio' => $primeraEmisionAbierta ?? today()->toDateString(), 'fecha_fin' => today()->toDateString()])" icon="descarga" variant="secondary" size="sm">Cartera completa PDF</x-ui.button>
                <x-ui.button :href="route('reportes.exportar', ['tipo' => 'cartera', 'formato' => 'xlsx', 'fecha_inicio' => $primeraEmisionAbierta ?? today()->toDateString(), 'fecha_fin' => today()->toDateString()])" icon="excel" variant="secondary" size="sm">Excel</x-ui.button>
                <x-ui.button :href="route('abonos.create')" icon="billete" size="sm">Registrar abono</x-ui.button>
            </div>
        </div>
        <div class="rounded-2xl border border-cobre/40 bg-cobre/[0.08] px-6 py-5 lg:min-w-72">
            <p class="eyebrow text-cobre">Total en mora</p>
            <p class="mt-2 font-display text-3xl font-bold text-cobre"><x-ui.money :value="$resumen['en_mora']" /></p>
            <p class="mt-1 text-xs text-niebla">{{ $resumen['facturas_vencidas'] }} facturas vencidas · <span class="tnum">{{ number_format($resumen['indice_morosidad'], 1) }}%</span> de la cartera</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi label="Cartera por cobrar" icon="billete">
            <x-ui.money :value="$resumen['por_cobrar']" />
            <x-slot:pie>{{ $resumen['facturas_abiertas'] }} facturas con saldo</x-slot:pie>
        </x-ui.kpi>
        <x-ui.kpi label="Al día (sin vencer)" tone="menta" icon="check">
            <x-ui.money :value="$resumen['al_dia']" />
            <x-slot:pie>Saldo dentro del plazo de crédito</x-slot:pie>
        </x-ui.kpi>
        <x-ui.kpi label="Por vencer" tone="oro" icon="reloj">
            <x-ui.money :value="$resumen['por_vencer_monto']" />
            <x-slot:pie>{{ $resumen['facturas_por_vencer'] }} facturas vencen en {{ config('contable.dias_alerta_previa') }} días o menos</x-slot:pie>
        </x-ui.kpi>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card p-5" aria-labelledby="titulo-tramos">
            <p class="eyebrow text-cobre">Antigüedad de saldos vencidos</p>
            <h2 id="titulo-tramos" class="mt-1 text-base font-semibold">Mora por tramo de días</h2>
            <table class="mt-4 w-full text-sm">
                <caption class="sr-only">Saldo vencido agrupado por días de mora</caption>
                <thead class="sr-only"><tr><th scope="col">Tramo</th><th scope="col">Facturas</th><th scope="col">Saldo</th></tr></thead>
                <tbody class="flex flex-col gap-4">
                    @foreach ($resumen['tramos'] as $tramo)
                        <tr class="flex flex-col gap-1.5">
                            <td class="flex items-baseline justify-between gap-3">
                                <span class="text-niebla">{{ $tramo['etiqueta'] }} <span class="text-xs">· {{ $tramo['cantidad'] }} fact.</span></span>
                                <x-ui.money :value="$tramo['monto']" class="font-semibold" />
                            </td>
                            <td class="h-2 rounded-full bg-tinta/[0.05]" aria-hidden="true">
                                <div class="h-full rounded-full bg-cobre" style="width: {{ (float) $tramo['monto'] > 0 ? max(2, round((float) $tramo['monto'] / $maxTramo * 100)) : 0 }}%"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="card p-5" aria-labelledby="titulo-deudores">
            <p class="eyebrow text-lila">Concentración de cartera</p>
            <h2 id="titulo-deudores" class="mt-1 text-base font-semibold">Principales deudores</h2>
            <ol class="mt-4 flex flex-col divide-y divide-tinta/[0.05]">
                @forelse ($resumen['top_deudores'] as $fila)
                    <li class="flex items-center gap-3 py-2.5">
                        <span class="tnum grid size-7 shrink-0 place-items-center rounded-lg bg-tinta/[0.05] text-xs font-semibold text-niebla">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('clientes.show', $fila['cliente']) }}" class="block truncate text-sm font-semibold hover:text-menta">{{ $fila['cliente']->razon_social }}</a>
                            <p class="text-xs text-niebla">{{ $fila['facturas'] }} {{ $fila['facturas'] === 1 ? 'factura' : 'facturas' }}{{ $fila['dias_mora'] > 0 ? ' · '.$fila['dias_mora'].' días de mora máx.' : ' · al día' }}</p>
                        </div>
                        <x-ui.money :value="$fila['saldo']" class="text-sm font-semibold" />
                    </li>
                @empty
                    <li><x-ui.empty icon="check" title="Sin deudores">No hay saldos pendientes de cobro.</x-ui.empty></li>
                @endforelse
            </ol>
        </section>
    </div>

    <section class="card min-w-0" aria-labelledby="titulo-cartera">
        <div class="flex flex-col gap-4 px-5 pt-5 pb-3">
            <h2 id="titulo-cartera" class="text-base font-semibold">Facturas con saldo pendiente <span class="tnum text-niebla">({{ $facturas->total() }})</span></h2>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <nav aria-label="Filtrar por condición" class="flex flex-wrap gap-1 rounded-xl border border-tinta/10 bg-obsidiana/60 p-1">
                    @foreach ($condiciones as $valor => $texto)
                        @php $activa = $condicionActual === $valor; @endphp
                        <a href="{{ route('cartera.index', array_filter(['condicion' => $valor, 'buscar' => $filtros['buscar'] ?? null])) }}"
                           @if ($activa) aria-current="true" @endif
                           class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors {{ $activa ? 'bg-grafito-alto text-platino shadow-[inset_0_0_0_1px_rgb(0_245_160/0.4)]' : 'text-niebla hover:text-platino' }}">{{ $texto }}</a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('cartera.index') }}" role="search" class="flex w-full gap-2 lg:w-96">
                    @if ($condicionActual)<input type="hidden" name="condicion" value="{{ $condicionActual }}">@endif
                    <label for="buscar" class="sr-only">Buscar por cliente o número de factura</label>
                    <div class="relative flex-1">
                        <x-ui.icon name="buscar" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-niebla" />
                        <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Cliente o n.º de factura" class="campo pl-9" maxlength="100">
                    </div>
                    <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Factura</th>
                        <th scope="col">Vencimiento</th>
                        <th scope="col" class="text-right">Saldo pendiente</th>
                        <th scope="col" class="text-right">Días mora</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facturas as $factura)
                        @php
                            $indicador = $factura->indicadorCartera();
                            $dias = $factura->diasMora();
                        @endphp
                        <tr>
                            <td class="max-w-64">
                                <a href="{{ route('clientes.show', $factura->cliente) }}" class="block truncate font-semibold hover:text-menta" title="{{ $factura->cliente->razon_social }}">{{ $factura->cliente->razon_social }}</a>
                                <span class="tnum text-xs text-niebla">{{ $factura->cliente->telefono ?? $factura->cliente->ruc_cedula }}</span>
                            </td>
                            <td><a href="{{ route('facturas.show', $factura) }}" class="tnum text-niebla hover:text-platino">{{ $factura->numero_factura }}</a></td>
                            <td class="tnum {{ $dias > 0 ? 'text-cobre' : 'text-niebla' }}">{{ $factura->fecha_vencimiento->format('d/m/Y') }}</td>
                            <td class="text-right font-semibold"><x-ui.money :value="$factura->saldo()" /></td>
                            <td class="tnum text-right font-display font-bold {{ $dias > 0 ? 'text-cobre' : ($factura->estaPorVencer() ? 'text-oro' : 'text-menta') }}">
                                @if ($dias > 0)
                                    {{ $dias }} días
                                @elseif ($factura->estaPorVencer())
                                    {{ $factura->diasParaVencer() === 0 ? 'Vence hoy' : 'Vence en '.$factura->diasParaVencer().' d' }}
                                @else
                                    Al día
                                @endif
                            </td>
                            <td><x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge></td>
                            <td class="text-right">
                                <x-ui.button :href="route('abonos.create', ['factura' => $factura->id])" variant="ghost" size="sm" icon="billete" aria-label="Registrar abono a {{ $factura->numero_factura }}">Abonar</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="7" icon="check" title="No hay facturas en esta condición">
                            @if (array_filter($filtros))
                                <a href="{{ route('cartera.index') }}" class="text-menta underline underline-offset-4">Ver toda la cartera</a>
                            @else
                                No existen saldos pendientes de cobro.
                            @endif
                        </x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $facturas->links() }}</div>
    </section>
</x-layouts.app>
