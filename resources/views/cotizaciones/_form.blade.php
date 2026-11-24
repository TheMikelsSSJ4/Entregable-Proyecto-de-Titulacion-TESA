{{--
    Formulario maestro-detalle de cotización.
    Requiere $cotizacion, $clientes, $productos, $lineasIniciales, $clienteSeleccionado,
    $textoBoton y $urlCancelar.
--}}
@php
    $configuracion = [
        'productos' => $productos,
        'lineas' => old('lineas', $lineasIniciales),
        'errores' => collect($errors->getMessages())
            ->filter(fn ($mensajes, $clave) => str_starts_with($clave, 'lineas.'))
            ->map(fn ($mensajes) => $mensajes[0])
            ->all(),
        'puedeEditarCatalogo' => $puedeEditarCatalogo,
    ];
@endphp

<div x-data="documentoComercial(@js($configuracion))" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
    <div class="flex min-w-0 flex-col gap-6">
        <div class="card flex flex-col gap-5 p-5 sm:p-6">
            <div>
                <p class="eyebrow text-lila">Generación de cotizaciones</p>
                <h2 class="mt-1 text-lg font-semibold">{{ $cotizacion->exists ? 'Editar cotización '.$cotizacion->numero : 'Emitir proforma comercial' }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-ui.select label="Cliente" name="cliente_id" required placeholder="Seleccione el cliente" class="md:col-span-2"
                    :value="$clienteSeleccionado"
                    :options="$clientes->mapWithKeys(fn ($c) => [$c->id => $c->razon_social.' · '.$c->ruc_cedula])->all()" />
                <x-ui.select label="Vendedor" name="vendedor_id" required class="md:col-span-2"
                    :value="$vendedorSeleccionado" :options="$vendedores"
                    hint="Quién realizó la venta. Puede ser distinto de quien la registra." />
                <x-ui.input label="Fecha de emisión" name="fecha_emision" type="date" required
                    :value="$cotizacion->fecha_emision?->toDateString()" max="{{ today()->toDateString() }}" />
                @if ($cotizacion->exists)
                    <x-ui.select label="Estado" name="estado" required :value="$cotizacion->estado"
                        :options="collect(\App\Enums\EstadoCotizacion::editables())->mapWithKeys(fn ($e) => [$e->value => $e->etiqueta()])->all()"
                        hint="«Facturada» se asigna automáticamente al emitir la factura." />
                @endif
            </div>
        </div>

        @include('documentos._lineas')
    </div>

    <aside aria-labelledby="titulo-resumen-cot">
        <div class="card sticky top-24 flex flex-col gap-4 p-5">
            <p id="titulo-resumen-cot" class="eyebrow text-cian">Resumen de la cotización</p>
            @include('documentos._totales')
            <x-ui.button type="submit" icon="check" variant="violet" class="w-full" data-enviando="Guardando…">{{ $textoBoton }}</x-ui.button>
            <x-ui.button :href="$urlCancelar" variant="ghost" class="w-full">Cancelar</x-ui.button>
        </div>
    </aside>
</div>
