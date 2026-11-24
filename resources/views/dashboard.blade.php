<x-layouts.app title="Dashboard" heading="Panel principal">
    @php
        $usuario = auth()->user();
        $totalVentas = (float) $ventasMes['total'];
        $pctContado = $totalVentas > 0 ? round((float) $ventasMes['contado'] / $totalVentas * 100) : 0;
        $pctCredito = $totalVentas > 0 ? 100 - $pctContado : 0;
    @endphp

    <div>
        <p class="eyebrow text-menta">Radar contable · {{ now()->translatedFormat('F Y') }}</p>
        <p class="mt-1 text-sm text-niebla">Hola, {{ \Illuminate\Support\Str::of($usuario->nombre_completo)->before(' ') }}. Este es el estado de la cartera por cobrar al {{ now()->format('d/m/Y') }}.</p>
    </div>

    @if ($verFinanzas)
        {{-- Indicadores financieros --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.kpi label="Cartera por cobrar" icon="billete">
                <x-ui.money :value="$resumen['por_cobrar']" />
                <x-slot:pie>{{ $resumen['facturas_abiertas'] }} facturas con saldo pendiente</x-slot:pie>
            </x-ui.kpi>
            <x-ui.kpi label="Recaudado este mes" tone="menta" icon="subida">
                <x-ui.money :value="$recaudacionMes['total']" />
                <x-slot:pie>{{ $recaudacionMes['cantidad'] }} pagos · contado y abonos</x-slot:pie>
            </x-ui.kpi>
            <x-ui.kpi label="Total en mora" tone="cobre" icon="alerta">
                <x-ui.money :value="$resumen['en_mora']" />
                <x-slot:pie>Índice de morosidad: <span class="tnum font-semibold text-platino">{{ number_format($resumen['indice_morosidad'], 1) }}%</span></x-slot:pie>
            </x-ui.kpi>
            <x-ui.kpi label="Alertas activas" tone="{{ $resumen['alertas_activas'] > 0 ? 'cobre' : 'menta' }}" icon="reloj">
                <span class="tnum">{{ $resumen['alertas_activas'] }}</span>
                <x-slot:pie>{{ $resumen['facturas_vencidas'] }} vencidas · {{ $resumen['facturas_por_vencer'] }} por vencer en {{ config('contable.dias_alerta_previa') }} días</x-slot:pie>
            </x-ui.kpi>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Alertas de vencimiento --}}
            <section class="card min-w-0 xl:col-span-2" aria-labelledby="titulo-alertas">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5 pb-3">
                    <div>
                        <p class="eyebrow text-cobre">RF-05 · Alertas</p>
                        <h2 id="titulo-alertas" class="mt-1 text-base font-semibold">Vencimientos que requieren gestión</h2>
                    </div>
                    <x-ui.button :href="route('cartera.index')" variant="secondary" size="sm">Ver cartera completa</x-ui.button>
                </div>
                <div class="overflow-x-auto">
                    <table class="tabla">
                        <caption class="sr-only">Facturas vencidas o próximas a vencer</caption>
                        <thead>
                            <tr>
                                <th scope="col">Cliente</th>
                                <th scope="col">Factura</th>
                                <th scope="col">Vencimiento</th>
                                <th scope="col" class="text-right">Saldo</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alertas as $factura)
                                @php $indicador = $factura->indicadorCartera(); @endphp
                                <tr>
                                    <td class="max-w-56 truncate font-medium" title="{{ $factura->cliente->razon_social }}">{{ $factura->cliente->razon_social }}</td>
                                    <td><a href="{{ route('facturas.show', $factura) }}" class="tnum text-niebla underline-offset-4 hover:text-platino hover:underline">{{ $factura->numero_factura }}</a></td>
                                    <td class="tnum whitespace-nowrap">
                                        <span class="{{ $factura->estaVencida() ? 'text-cobre' : 'text-oro' }}">{{ $factura->fecha_vencimiento->format('d/m/Y') }}</span>
                                        <span class="block text-xs text-niebla">
                                            {{ $factura->estaVencida() ? $factura->diasMora().' días de mora' : ($factura->diasParaVencer() === 0 ? 'Vence hoy' : 'En '.$factura->diasParaVencer().' días') }}
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold"><x-ui.money :value="$factura->saldo()" /></td>
                                    <td><x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge></td>
                                </tr>
                            @empty
                                <x-ui.empty :colspan="5" icon="check" title="Sin alertas de vencimiento">
                                    Ninguna factura a crédito está vencida ni vence en los próximos {{ config('contable.dias_alerta_previa') }} días.
                                </x-ui.empty>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Acciones rápidas + ventas del mes --}}
            <div class="flex min-w-0 flex-col gap-6">
                <section class="card p-5" aria-labelledby="titulo-acciones">
                    <h2 id="titulo-acciones" class="eyebrow text-lila">Acciones rápidas</h2>
                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3 xl:grid-cols-1">
                        <x-ui.button :href="route('facturas.create')" icon="mas" variant="violet">Registrar factura</x-ui.button>
                        <x-ui.button :href="route('abonos.create')" icon="billete">Registrar abono</x-ui.button>
                        <x-ui.button :href="route('reportes.index')" icon="grafico" variant="secondary">Reportes PDF / Excel</x-ui.button>
                    </div>
                </section>

                <section class="card p-5" aria-labelledby="titulo-ventas">
                    <p class="eyebrow text-cian">Ventas del mes</p>
                    <h2 id="titulo-ventas" class="mt-2 font-display text-2xl font-bold"><x-ui.money :value="$ventasMes['total']" /></h2>
                    <p class="text-xs text-niebla">{{ $ventasMes['cantidad'] }} facturas emitidas (sin anuladas)</p>

                    @if ($totalVentas > 0)
                        <div class="mt-4 flex h-2.5 gap-0.5 overflow-hidden rounded-full" role="img" aria-label="Contado {{ $pctContado }} por ciento, crédito {{ $pctCredito }} por ciento">
                            @if ($pctContado > 0)<span class="h-full rounded-l-full bg-esmeralda" style="width: {{ $pctContado }}%" title="Contado: ${{ number_format((float) $ventasMes['contado'], 2) }}"></span>@endif
                            @if ($pctCredito > 0)<span class="h-full rounded-r-full bg-violeta" style="width: {{ $pctCredito }}%" title="Crédito: ${{ number_format((float) $ventasMes['credito'], 2) }}"></span>@endif
                        </div>
                    @endif
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="flex items-center gap-2 text-xs text-niebla"><span class="size-2 rounded-full bg-esmeralda" aria-hidden="true"></span>Contado · {{ $pctContado }}%</dt>
                            <dd class="mt-0.5 font-semibold"><x-ui.money :value="$ventasMes['contado']" /></dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-2 text-xs text-niebla"><span class="size-2 rounded-full bg-violeta" aria-hidden="true"></span>Crédito · {{ $pctCredito }}%</dt>
                            <dd class="mt-0.5 font-semibold"><x-ui.money :value="$ventasMes['credito']" /></dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Antigüedad de la cartera vencida --}}
            @php $maxTramo = max(1, ...array_map(fn ($t) => (float) $t['monto'], $resumen['tramos'])); @endphp
            <section class="card p-5" aria-labelledby="titulo-antiguedad">
                <p class="eyebrow text-cobre">Antigüedad de mora</p>
                <h2 id="titulo-antiguedad" class="mt-1 text-base font-semibold">Saldo vencido por tramo</h2>
                <ul class="mt-5 flex flex-col gap-4">
                    @foreach ($resumen['tramos'] as $tramo)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="text-niebla">{{ $tramo['etiqueta'] }} <span class="text-xs">· {{ $tramo['cantidad'] }} fact.</span></span>
                                <x-ui.money :value="$tramo['monto']" class="font-semibold text-platino" />
                            </div>
                            <div class="mt-1.5 h-2 rounded-full bg-tinta/[0.05]" aria-hidden="true">
                                <div class="h-full rounded-full bg-cobre" style="width: {{ (float) $tramo['monto'] > 0 ? max(2, round((float) $tramo['monto'] / $maxTramo * 100)) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Últimos pagos --}}
            <section class="card min-w-0 p-5 xl:col-span-2" aria-labelledby="titulo-pagos">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="eyebrow text-menta">Ingresos recientes</p>
                        <h2 id="titulo-pagos" class="mt-1 text-base font-semibold">Últimos pagos y abonos</h2>
                    </div>
                    <x-ui.button :href="route('facturas.index')" variant="ghost" size="sm">Transacciones</x-ui.button>
                </div>
                <ul class="mt-4 flex flex-col gap-2">
                    @forelse ($ultimosPagos as $pago)
                        <li class="flex items-center gap-3 rounded-xl border border-tinta/[0.05] bg-obsidiana/40 px-4 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full border border-menta/40 text-menta"><x-ui.icon name="subida" class="size-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $pago->factura->cliente->razon_social }}</p>
                                <p class="truncate text-xs text-niebla">
                                    <span class="tnum">{{ $pago->factura->numero_factura }}</span> · {{ $pago->metodo_pago->etiqueta() }} · {{ $pago->fecha_pago->format('d/m/Y') }}
                                    · {{ $pago->factura->tipo_transaccion->etiqueta() }}
                                </p>
                            </div>
                            <x-ui.money :value="$pago->monto_abonado" sign="+" class="font-display text-sm font-bold text-menta sm:text-base" />
                        </li>
                    @empty
                        <x-ui.empty icon="billete" title="Aún no hay pagos registrados">Los abonos y ventas al contado aparecerán aquí.</x-ui.empty>
                    @endforelse
                </ul>
            </section>
        </div>
    @else
        {{-- Vista del vendedor --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.kpi label="Mis ventas del mes" tone="menta" icon="subida">
                <x-ui.money :value="$ventasMes['total']" />
                <x-slot:pie>{{ $ventasMes['cantidad'] }} facturas registradas</x-slot:pie>
            </x-ui.kpi>
            <x-ui.kpi label="Cotizaciones abiertas" tone="violeta" icon="documento">
                <span class="tnum">{{ $cotizacionesPendientes }}</span>
                <x-slot:pie>Pendientes o aprobadas por facturar</x-slot:pie>
            </x-ui.kpi>
            <x-ui.kpi label="Clientes registrados" tone="cian" icon="edificio">
                <span class="tnum">{{ $clientesRegistrados }}</span>
                <x-slot:pie>Directorio de la empresa</x-slot:pie>
            </x-ui.kpi>
        </div>

        <section class="card p-5" aria-labelledby="titulo-acciones-v">
            <h2 id="titulo-acciones-v" class="eyebrow text-lila">Acciones rápidas</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button :href="route('cotizaciones.create')" icon="mas" variant="violet">Nueva cotización</x-ui.button>
                <x-ui.button :href="route('facturas.create')" icon="mas">Registrar factura</x-ui.button>
                <x-ui.button :href="route('clientes.create')" icon="edificio" variant="secondary">Nuevo cliente</x-ui.button>
            </div>
        </section>

        <section class="card min-w-0" aria-labelledby="titulo-mis-facturas">
            <div class="px-5 pt-5 pb-3">
                <p class="eyebrow text-menta">Mis transacciones</p>
                <h2 id="titulo-mis-facturas" class="mt-1 text-base font-semibold">Últimas facturas registradas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Factura</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Tipo</th>
                            <th scope="col" class="text-right">Total</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ultimasFacturas as $factura)
                            @php $indicador = $factura->indicadorCartera(); @endphp
                            <tr>
                                <td><a href="{{ route('facturas.show', $factura) }}" class="tnum font-medium hover:text-menta">{{ $factura->numero_factura }}</a></td>
                                <td class="max-w-56 truncate">{{ $factura->cliente->razon_social }}</td>
                                <td class="text-niebla">{{ $factura->tipo_transaccion->etiqueta() }}</td>
                                <td class="text-right font-semibold"><x-ui.money :value="$factura->total" /></td>
                                <td><x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge></td>
                            </tr>
                        @empty
                            <x-ui.empty :colspan="5" title="Todavía no registra facturas">Use «Registrar factura» para su primera venta.</x-ui.empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.app>
