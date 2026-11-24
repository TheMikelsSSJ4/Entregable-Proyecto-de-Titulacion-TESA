<x-layouts.app title="Nueva venta" heading="Registrar factura de venta">
    @php
        $hoy = today()->toDateString();
        $plazoDefecto = (int) config('contable.plazo_credito_defecto');
        $configuracion = [
            'productos' => $productos,
            'lineas' => old('lineas', $lineasIniciales),
            'errores' => collect($errors->getMessages())
                ->filter(fn ($mensajes, $clave) => str_starts_with($clave, 'lineas.'))
                ->map(fn ($mensajes) => $mensajes[0])
                ->all(),
            'tipo' => old('tipo_transaccion', 'credito'),
            'emision' => old('fecha_emision', $hoy),
            'vencimiento' => old('fecha_vencimiento', today()->addDays($plazoDefecto)->toDateString()),
            'plazo' => old('fecha_vencimiento') ? null : $plazoDefecto,
            'puedeEditarCatalogo' => $puedeEditarCatalogo,
        ];
    @endphp

    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('facturas.index') }}" class="inline-flex items-center gap-1 text-niebla hover:text-platino"><x-ui.icon name="atras" class="size-4" />Transacciones</a>
    </div>

    @if ($cotizacion)
        <div class="flex items-start gap-3 rounded-xl border border-violeta/40 bg-violeta/[0.08] px-4 py-3 text-sm" role="status">
            <x-ui.icon name="documento" class="mt-0.5 size-4 text-lila" />
            <p>Facturando la cotización <strong class="tnum">{{ $cotizacion->numero }}</strong> de {{ $cotizacion->cliente->razon_social }} por <x-ui.money :value="$cotizacion->total" class="font-semibold" />. Los productos se cargaron con los precios vigentes del catálogo. Al guardar, la cotización quedará como <em>Facturada</em>.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('facturas.store') }}" x-data="documentoComercial(@js($configuracion))"
          class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]" novalidate>
        @csrf
        @if ($cotizacion)
            <input type="hidden" name="cotizacion_id" value="{{ $cotizacion->id }}">
        @endif

        <div class="flex min-w-0 flex-col gap-6">
            <div class="card flex flex-col gap-6 p-5 sm:p-6">
                <div>
                    <p class="eyebrow text-lila">RF-02 · Registro de transacción</p>
                    <h2 class="mt-1 text-lg font-semibold">Datos del comprobante</h2>
                </div>

                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-2 text-sm font-medium">Tipo de transacción <span class="text-cobre" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2 rounded-xl border border-tinta/10 bg-obsidiana/60 p-1">
                        @foreach (\App\Enums\TipoTransaccion::cases() as $tipo)
                            <label class="cursor-pointer">
                                <input type="radio" name="tipo_transaccion" value="{{ $tipo->value }}" x-model="tipo" class="peer sr-only">
                                <span class="flex flex-col items-center gap-0.5 rounded-lg px-3 py-2.5 text-center text-sm font-semibold text-niebla transition-colors peer-checked:bg-grafito-alto peer-checked:text-platino peer-checked:shadow-[inset_0_0_0_1px_rgb(0_245_160/0.45)] peer-focus-visible:outline-2 peer-focus-visible:outline-menta">
                                    {{ $tipo->etiqueta() }}
                                    <span class="text-xs font-normal text-niebla">{{ $tipo === \App\Enums\TipoTransaccion::Contado ? 'Se registra pagada' : 'Genera cuenta por cobrar' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('tipo_transaccion')<p class="text-xs text-cobre" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-ui.select label="Cliente" name="cliente_id" required placeholder="Seleccione el cliente" class="md:col-span-2"
                        :value="$clienteSeleccionado"
                        :options="$clientes->mapWithKeys(fn ($c) => [$c->id => $c->razon_social.' · '.$c->ruc_cedula])->all()"
                        hint="¿No está en la lista? Regístrelo primero en el Módulo de Clientes." />

                    <x-ui.select label="Vendedor" name="vendedor_id" required class="md:col-span-2"
                        :value="$vendedorSeleccionado" :options="$vendedores"
                        hint="Quién realizó la venta. Puede ser distinto de quien la registra." />
                    <x-ui.input label="Número de factura (SRI)" name="numero_factura" :value="$numeroSugerido" required maxlength="17" class="tnum"
                        pattern="\d{3}-\d{3}-\d{9}" inputmode="numeric" autocomplete="off" hint="Formato 001-001-000000123. Se sugiere el siguiente secuencial." />
                    <x-ui.input label="Fecha de emisión" name="fecha_emision" type="date" max="{{ $hoy }}" required x-model="emision" />
                </div>

                {{-- Sólo crédito --}}
                <div x-show="tipo === 'credito'" class="flex flex-col gap-3 rounded-xl border border-violeta/25 bg-violeta/[0.04] p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:items-end">
                        <x-ui.input label="Fecha de vencimiento" name="fecha_vencimiento" type="date" required
                            x-model="vencimiento" x-bind:disabled="tipo !== 'credito'" x-on:input="plazo = null"
                            hint="Base para el cálculo de días de mora." />
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium" id="plazos-titulo">Plazo rápido</span>
                            <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="plazos-titulo">
                                @foreach (config('contable.plazos_credito') as $plazo)
                                    <button type="button" x-on:click="aplicarPlazo({{ $plazo }})" x-bind:aria-pressed="(plazo === {{ $plazo }}).toString()"
                                        class="rounded-lg border border-tinta/10 px-2.5 py-1.5 text-xs font-semibold text-niebla hover:text-platino aria-pressed:border-menta/60 aria-pressed:text-menta">
                                        {{ $plazo }} días
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sólo contado --}}
                <div x-show="tipo === 'contado'" x-cloak class="grid grid-cols-1 gap-4 rounded-xl border border-menta/20 bg-menta/[0.03] p-4 md:grid-cols-2">
                    <x-ui.select label="Método de pago" name="metodo_pago" value="efectivo" required x-bind:disabled="tipo !== 'contado'"
                        :options="collect(\App\Enums\MetodoPago::cases())->mapWithKeys(fn ($m) => [$m->value => $m->etiqueta()])->all()" />
                    <x-ui.input label="Comprobante / referencia" name="comprobante_referencia" maxlength="50" x-bind:disabled="tipo !== 'contado'"
                        hint="Opcional: n.º de transferencia, cheque o voucher." />
                </div>
            </div>

            @include('documentos._lineas')
        </div>

        {{-- Resumen --}}
        <aside aria-labelledby="titulo-resumen">
            <div class="card sticky top-24 flex flex-col gap-4 p-5">
                <p id="titulo-resumen" class="eyebrow text-cian">Resumen de la factura</p>
                @include('documentos._totales')
                <x-ui.button type="submit" icon="check" class="w-full" data-enviando="Registrando…">Registrar factura</x-ui.button>
                <x-ui.button :href="route('facturas.index')" variant="ghost" class="w-full">Cancelar</x-ui.button>
            </div>
        </aside>
    </form>
</x-layouts.app>
