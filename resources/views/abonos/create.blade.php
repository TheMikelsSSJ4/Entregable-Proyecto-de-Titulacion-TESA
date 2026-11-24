<x-layouts.app title="Registrar abono" heading="Registrar pago o abono">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('facturas.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Transacciones</a>
    </div>

    @if ($facturas->isEmpty())
        <section class="card">
            <x-ui.empty icon="check" title="No hay facturas con saldo pendiente">
                Todas las facturas a crédito están pagadas. Las nuevas ventas a crédito aparecerán aquí.
            </x-ui.empty>
        </section>
    @else
        @php $facturaInicial = old('factura_id', $facturaSeleccionada); @endphp
        <form method="POST" action="{{ route('abonos.store') }}" data-abono-form class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]" novalidate>
            @csrf
            <div class="card flex flex-col gap-6 p-5 sm:p-6">
                <div>
                    <p class="eyebrow text-menta">RF-04 · Control de pagos y créditos</p>
                    <h2 class="mt-1 text-lg font-semibold">Aplicar pago a una factura</h2>
                    <p class="mt-1 text-sm text-niebla">Seleccione la factura; el sistema valida que el abono no supere el saldo y recalcula el estado.</p>
                </div>

                <x-ui.select label="Factura con saldo" name="factura_id" required placeholder="Seleccione una factura" :value="$facturaInicial">
                    @foreach ($facturas->groupBy(fn ($f) => $f->cliente->razon_social) as $cliente => $grupo)
                        <optgroup label="{{ $cliente }}">
                            @foreach ($grupo as $factura)
                                <option value="{{ $factura->id }}" @selected((string) $facturaInicial === (string) $factura->id)
                                    data-saldo="{{ $factura->saldo() }}" data-total="{{ $factura->total }}" data-abonado="{{ $factura->totalAbonado() }}"
                                    data-cliente="{{ $factura->cliente->razon_social }}" data-numero="{{ $factura->numero_factura }}"
                                    data-vence="{{ $factura->fecha_vencimiento->format('d/m/Y') }}{{ $factura->estaVencida() ? ' · '.$factura->diasMora().' días de mora' : '' }}">
                                    {{ $factura->numero_factura }} — saldo ${{ number_format((float) $factura->saldo(), 2) }}{{ $factura->estaVencida() ? ' (vencida)' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.select>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <x-ui.input label="Monto del abono" name="monto_abonado" type="number" step="0.01" min="0.01" inputmode="decimal" required class="tnum" placeholder="0.00" />
                        <button type="button" data-pagar-saldo class="self-start text-xs font-semibold text-menta underline-offset-4 hover:underline">Usar saldo total</button>
                    </div>
                    <x-ui.input label="Fecha de pago" name="fecha_pago" type="date" :value="today()->toDateString()" max="{{ today()->toDateString() }}" required />
                    <x-ui.select label="Método de pago" name="metodo_pago" value="transferencia" required
                        :options="collect(\App\Enums\MetodoPago::cases())->mapWithKeys(fn ($m) => [$m->value => $m->etiqueta()])->all()" />
                    <x-ui.input label="Comprobante / referencia" name="comprobante_referencia" maxlength="50" hint="Obligatorio si el pago no es en efectivo." />
                </div>
            </div>

            <aside aria-labelledby="titulo-factura-sel">
                <div class="card sticky top-24 flex flex-col gap-4 p-5">
                    <p id="titulo-factura-sel" class="eyebrow text-cian">Factura seleccionada</p>
                    <div data-panel-factura hidden>
                        <dl class="flex flex-col gap-3 text-sm" aria-live="polite">
                            <div><dt class="text-xs text-niebla">Cliente</dt><dd data-dato="cliente" class="font-semibold"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-niebla">Factura</dt><dd data-dato="numero" class="tnum"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-niebla">Vencimiento</dt><dd data-dato="vence" class="tnum text-right"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-niebla">Total</dt><dd data-dato="total" class="tnum"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-niebla">Abonado</dt><dd data-dato="abonado" class="tnum text-menta"></dd></div>
                            <div class="flex items-baseline justify-between gap-3 border-t border-tinta/[0.06] pt-3"><dt class="font-semibold">Saldo actual</dt><dd data-dato="saldo" class="tnum font-display text-xl font-bold"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-niebla">Saldo después del abono</dt><dd data-salida-restante class="tnum font-semibold">—</dd></div>
                        </dl>
                    </div>
                    <x-ui.button type="submit" icon="check" class="w-full" data-enviando="Registrando…">Registrar abono</x-ui.button>
                    <x-ui.button :href="route('facturas.index')" variant="ghost" class="w-full">Cancelar</x-ui.button>
                </div>
            </aside>
        </form>
    @endif
</x-layouts.app>
