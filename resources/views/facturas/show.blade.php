<x-layouts.app :title="'Factura '.$factura->numero_factura" heading="Detalle de la transacción">
    @php
        $indicador = $factura->indicadorCartera();
        $abonado = (float) $factura->totalAbonado();
        $total = (float) $factura->total;
        $porcentajePagado = $total > 0 ? min(100, round($abonado / $total * 100)) : 0;
        $puedeAbonar = $factura->estaAbierta() && auth()->user()->can('create', \App\Models\PagoAbono::class);
        $puedeAnular = auth()->user()->can('anular', $factura) && $factura->pagos->isEmpty();
        $puedeRevertir = $factura->pagos->isNotEmpty() && $factura->estado !== \App\Enums\EstadoFactura::Anulada
            && auth()->user()->can('delete', $factura->pagos->first());
    @endphp

    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('facturas.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Transacciones</a>
    </div>

    <section class="card flex flex-col gap-6 p-5 sm:p-6" aria-labelledby="titulo-factura">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="eyebrow text-lila">Factura de venta · {{ $factura->tipo_transaccion->etiqueta() }}</p>
                <h2 id="titulo-factura" class="mt-1 flex flex-wrap items-center gap-3 text-xl font-semibold">
                    <span class="tnum">{{ $factura->numero_factura }}</span>
                    <x-ui.badge :tone="$indicador['tono']">{{ $indicador['etiqueta'] }}</x-ui.badge>
                </h2>
                <p class="mt-1 text-sm text-niebla">
                    <a href="{{ route('clientes.show', $factura->cliente) }}" class="font-medium text-platino hover:text-menta">{{ $factura->cliente->razon_social }}</a>
                    · {{ $factura->cliente->tipoIdentificacion() }} <span class="tnum">{{ $factura->cliente->ruc_cedula }}</span>
                    @if ($factura->cotizacion)
                        · desde <a href="{{ route('cotizaciones.show', $factura->cotizacion) }}" class="tnum text-lila hover:underline">{{ $factura->cotizacion->numero }}</a>
                    @endif
                </p>
                <p class="mt-3 inline-flex flex-wrap items-center gap-2 rounded-lg border border-violeta/30 bg-violeta/[0.08] px-3 py-1.5 text-sm">
                    <x-ui.icon name="usuario" class="size-4 text-lila" />
                    <span class="eyebrow text-lila">Vendedor:</span>
                    <span class="font-semibold">{{ \Illuminate\Support\Str::upper($factura->vendedor->nombre_completo) }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                <x-ui.button :href="route('facturas.pdf', ['factura' => $factura, 'imprimir' => 1])" icon="imprimir" variant="secondary" size="sm" target="_blank" rel="noopener">Imprimir PDF</x-ui.button>
                <x-ui.button :href="route('facturas.pdf', $factura)" icon="descarga" variant="secondary" size="sm">Descargar PDF</x-ui.button>
                @if ($puedeAnular)
                    <form method="POST" action="{{ route('facturas.anular', $factura) }}"
                          data-confirm="La factura {{ $factura->numero_factura }} quedará anulada y dejará de sumar en la cartera. Esta acción no se puede deshacer."
                          data-confirm-boton="Anular factura">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit" icon="anular" variant="danger" size="sm">Anular</x-ui.button>
                    </form>
                @endif
            </div>
        </div>

        @if ($factura->estaVencida())
            <div role="alert" class="flex items-start gap-3 rounded-xl border border-cobre/40 bg-cobre/[0.08] px-4 py-3 text-sm">
                <x-ui.icon name="alerta" class="mt-0.5 size-4 text-cobre" />
                <p><strong class="text-cobre">{{ $factura->diasMora() }} días de mora.</strong> Venció el {{ $factura->fecha_vencimiento->format('d/m/Y') }} con un saldo de <x-ui.money :value="$factura->saldo()" class="font-semibold" />.</p>
            </div>
        @elseif ($factura->estaPorVencer())
            <div role="status" class="flex items-start gap-3 rounded-xl border border-oro/40 bg-oro/[0.06] px-4 py-3 text-sm">
                <x-ui.icon name="reloj" class="mt-0.5 size-4 text-oro" />
                <p>{{ $factura->diasParaVencer() === 0 ? 'Vence hoy.' : 'Vence en '.$factura->diasParaVencer().' días ('.$factura->fecha_vencimiento->format('d/m/Y').').' }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_20rem]">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm sm:grid-cols-4">
                <div><dt class="text-xs text-niebla">Emisión</dt><dd class="tnum mt-0.5 font-medium">{{ $factura->fecha_emision->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-niebla">Vencimiento</dt><dd class="tnum mt-0.5 font-medium">{{ $factura->esCredito() ? $factura->fecha_vencimiento->format('d/m/Y') : 'Contado' }}</dd></div>
                <div><dt class="text-xs text-niebla">Plazo</dt><dd class="tnum mt-0.5 font-medium">{{ $factura->esCredito() ? $factura->fecha_emision->diffInDays($factura->fecha_vencimiento).' días' : '—' }}</dd></div>
                <div class="min-w-0"><dt class="text-xs text-niebla">Registrada por</dt><dd class="mt-0.5 truncate font-medium">{{ $factura->usuario->nombre_completo }}</dd></div>
                <div><dt class="text-xs text-niebla">Subtotal</dt><dd class="mt-0.5 font-medium"><x-ui.money :value="$factura->subtotal" /></dd></div>
                <div><dt class="text-xs text-niebla">IVA</dt><dd class="mt-0.5 font-medium"><x-ui.money :value="$factura->iva" /></dd></div>
                <div><dt class="text-xs text-niebla">Total</dt><dd class="mt-0.5 font-display text-lg font-bold"><x-ui.money :value="$factura->total" /></dd></div>
                <div><dt class="text-xs text-niebla">Abonado</dt><dd class="mt-0.5 font-medium text-menta"><x-ui.money :value="$abonado" /></dd></div>
            </dl>

            <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/50 p-4">
                <p class="eyebrow text-niebla">Saldo pendiente</p>
                <p class="mt-2 font-display text-3xl font-bold {{ $factura->estaVencida() ? 'text-cobre' : 'text-platino' }}"><x-ui.money :value="$factura->saldo()" /></p>
                <div class="mt-4 h-2 rounded-full bg-tinta/[0.06]" role="progressbar" aria-label="Porcentaje pagado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentajePagado }}">
                    <div class="h-full rounded-full bg-menta" style="width: {{ $porcentajePagado }}%"></div>
                </div>
                <p class="mt-2 text-xs text-niebla"><span class="tnum">{{ $porcentajePagado }}%</span> pagado</p>
            </div>
        </div>
    </section>

    @include('documentos._detalle', ['documento' => $factura])

    <div class="grid grid-cols-1 gap-6 {{ $puedeAbonar ? 'xl:grid-cols-[1fr_24rem]' : '' }}">
        {{-- Historial de pagos --}}
        <section class="card min-w-0" aria-labelledby="titulo-pagos">
            <h2 id="titulo-pagos" class="px-5 pt-5 pb-3 text-base font-semibold">Pagos y abonos aplicados <span class="tnum text-niebla">({{ $factura->pagos->count() }})</span></h2>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Método</th>
                            <th scope="col">Comprobante</th>
                            <th scope="col">Registrado por</th>
                            <th scope="col" class="text-right">Monto</th>
                            @if ($puedeRevertir)
                                <th scope="col"><span class="sr-only">Acciones</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($factura->pagos as $pago)
                            <tr>
                                <td class="tnum">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                <td>{{ $pago->metodo_pago->etiqueta() }}</td>
                                <td class="tnum text-niebla">{{ $pago->comprobante_referencia ?? '—' }}</td>
                                <td class="text-niebla">{{ $pago->usuario->nombre_completo }}</td>
                                <td class="text-right font-semibold text-menta"><x-ui.money :value="$pago->monto_abonado" sign="+" /></td>
                                @if ($puedeRevertir)
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('abonos.destroy', $pago) }}"
                                              data-confirm="Se revertirá el pago de ${{ number_format((float) $pago->monto_abonado, 2) }} del {{ $pago->fecha_pago->format('d/m/Y') }}. El saldo de la factura aumentará."
                                              data-confirm-boton="Revertir abono">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" icon="revertir" aria-label="Revertir pago del {{ $pago->fecha_pago->format('d/m/Y') }}">Revertir</x-ui.button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-ui.empty :colspan="6" icon="billete" title="Sin pagos registrados">El saldo completo de la factura está pendiente de cobro.</x-ui.empty>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Registrar abono --}}
        @if ($puedeAbonar)
            <section class="card p-5" aria-labelledby="titulo-abono">
                <p class="eyebrow text-menta">RF-04 · Control de pagos</p>
                <h2 id="titulo-abono" class="mt-1 text-base font-semibold">Registrar abono</h2>
                <form method="POST" action="{{ route('abonos.store') }}" class="mt-4 flex flex-col gap-4" data-abono-form novalidate>
                    @csrf
                    <select name="factura_id" hidden aria-hidden="true" tabindex="-1">
                        <option value="{{ $factura->id }}" data-saldo="{{ $factura->saldo() }}" selected>{{ $factura->numero_factura }}</option>
                    </select>
                    <x-ui.input label="Monto del abono" name="monto_abonado" type="number" step="0.01" min="0.01" max="{{ $factura->saldo() }}" inputmode="decimal" required class="tnum" placeholder="0.00" />
                    <button type="button" data-pagar-saldo class="-mt-2 self-start text-xs font-semibold text-menta underline-offset-4 hover:underline">Pagar saldo total (<x-ui.money :value="$factura->saldo()" />)</button>
                    <p class="text-xs text-niebla">Saldo después del abono: <span data-salida-restante class="tnum font-semibold text-platino">—</span></p>
                    <x-ui.input label="Fecha de pago" name="fecha_pago" type="date" :value="today()->toDateString()" max="{{ today()->toDateString() }}" min="{{ $factura->fecha_emision->toDateString() }}" required />
                    <x-ui.select label="Método de pago" name="metodo_pago" value="transferencia" required
                        :options="collect(\App\Enums\MetodoPago::cases())->mapWithKeys(fn ($m) => [$m->value => $m->etiqueta()])->all()" />
                    <x-ui.input label="Comprobante / referencia" name="comprobante_referencia" maxlength="50" hint="Obligatorio si el pago no es en efectivo." />
                    <x-ui.button type="submit" icon="check" data-enviando="Registrando…">Aplicar abono</x-ui.button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.app>
