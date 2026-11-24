{{--
    Tabla maestro-detalle compartida por facturas y cotizaciones.
    Debe incluirse dentro de un elemento con x-data="documentoComercial(...)".
    Requiere $productos (catálogo activo).
--}}
<section class="card min-w-0" aria-labelledby="titulo-lineas">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5 pb-3">
        <div>
            <p class="eyebrow text-cian">Detalle del documento</p>
            <h2 id="titulo-lineas" class="mt-1 text-base font-semibold">Productos y servicios</h2>
        </div>
        <x-ui.button type="button" variant="secondary" size="sm" icon="mas" x-on:click="agregar()">Agregar producto</x-ui.button>
    </div>

    @error('lineas')
        <p class="mx-5 mb-2 flex items-center gap-1.5 rounded-lg bg-cobre/10 px-3 py-2 text-xs text-cobre" role="alert"><x-ui.icon name="alerta" class="size-3.5" />{{ $message }}</p>
    @enderror

    @if (count($productos) === 0)
        <x-ui.empty icon="caja" title="No hay productos activos en el catálogo">
            Pida a Gerencia que registre productos en el Módulo de Productos antes de emitir documentos.
        </x-ui.empty>
    @else
        <div class="overflow-x-auto">
            <table class="tabla min-w-[64rem]">
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col" class="w-28">Cantidad</th>
                        <th scope="col" class="w-36">P. unitario <span class="font-normal normal-case text-niebla">(sin IVA)</span></th>
                        <th scope="col" class="w-28">% Desc.</th>
                        <th scope="col" class="w-28">IVA</th>
                        <th scope="col" class="text-right">Total línea</th>
                        <th scope="col"><span class="sr-only">Quitar</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(linea, indice) in lineas" :key="linea.uid">
                        <tr class="align-top">
                            <td class="min-w-72">
                                <label :for="'producto-' + linea.uid" class="sr-only" x-text="'Producto de la línea ' + (indice + 1)"></label>
                                <select :id="'producto-' + linea.uid" :name="`lineas[${indice}][producto_id]`" x-model="linea.producto_id"
                                    x-on:change="completarDesdeCatalogo(linea)"
                                    data-selector-producto required class="campo" :class="error(indice, 'producto_id') && 'campo-error'"
                                    :aria-invalid="error(indice, 'producto_id') ? 'true' : 'false'">
                                    <option value="">Seleccione un producto…</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto['id'] }}" :disabled="usadoEnOtraLinea({{ $producto['id'] }}, indice)">{{ $producto['codigo'] }} · {{ $producto['nombre'] }}</option>
                                    @endforeach
                                </select>
                                <p x-show="error(indice, 'producto_id')" x-text="error(indice, 'producto_id')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>

                                {{-- Cambios manuales y opción de llevarlos al catálogo --}}
                                <div x-show="difiereDelCatalogo(linea)" x-cloak class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full border border-oro/40 bg-oro/10 px-2 py-0.5 font-semibold text-oro">Ajuste manual</span>
                                    <template x-if="puedeEditarCatalogo">
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 text-niebla hover:text-platino">
                                            <input type="checkbox" value="1" :name="`lineas[${indice}][actualizar_catalogo]`" x-model="linea.actualizar_catalogo" class="size-3.5 accent-menta">
                                            Guardar también en el catálogo
                                        </label>
                                    </template>
                                </div>
                                <p x-show="error(indice, 'actualizar_catalogo')" x-text="error(indice, 'actualizar_catalogo')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>
                            </td>
                            <td>
                                <label :for="'cantidad-' + linea.uid" class="sr-only" x-text="'Cantidad de la línea ' + (indice + 1)"></label>
                                <input :id="'cantidad-' + linea.uid" type="number" step="0.01" min="0.01" max="99999" inputmode="decimal" required
                                    :name="`lineas[${indice}][cantidad]`" x-model="linea.cantidad"
                                    class="campo tnum text-right" :class="error(indice, 'cantidad') && 'campo-error'">
                                <p x-show="error(indice, 'cantidad')" x-text="error(indice, 'cantidad')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>
                            </td>
                            <td>
                                <label :for="'precio-' + linea.uid" class="sr-only" x-text="'Precio unitario sin IVA de la línea ' + (indice + 1)"></label>
                                <input :id="'precio-' + linea.uid" type="number" step="0.01" min="0.01" max="99999999.99" inputmode="decimal"
                                    :name="`lineas[${indice}][precio_unitario]`" x-model="linea.precio_unitario"
                                    :disabled="!producto(linea.producto_id)"
                                    class="campo tnum text-right" :class="[error(indice, 'precio_unitario') && 'campo-error', precioManual(linea) && 'border-oro/60']"
                                    :aria-invalid="error(indice, 'precio_unitario') ? 'true' : 'false'">
                                <span x-show="precioManual(linea)" x-cloak class="mt-1 block text-right text-xs text-niebla" x-text="'Catálogo: ' + precioCatalogo(linea)"></span>
                                <p x-show="error(indice, 'precio_unitario')" x-text="error(indice, 'precio_unitario')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>
                            </td>
                            <td>
                                <label :for="'descuento-' + linea.uid" class="sr-only" x-text="'Porcentaje de descuento de la línea ' + (indice + 1)"></label>
                                <input :id="'descuento-' + linea.uid" type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                    :name="`lineas[${indice}][porcentaje_descuento]`" x-model="linea.porcentaje_descuento"
                                    :disabled="!producto(linea.producto_id)"
                                    class="campo tnum text-right" :class="[error(indice, 'porcentaje_descuento') && 'campo-error', descuentoManual(linea) && 'border-oro/60']">
                                <span x-show="montos(linea).descuento > 0" class="mt-1 block text-right text-xs text-cobre" x-text="'− ' + dinero(montos(linea).descuento)"></span>
                                <p x-show="error(indice, 'porcentaje_descuento')" x-text="error(indice, 'porcentaje_descuento')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>
                            </td>
                            <td>
                                <label :for="'iva-' + linea.uid" class="sr-only" x-text="'Tarifa de IVA de la línea ' + (indice + 1)"></label>
                                <select :id="'iva-' + linea.uid" :name="`lineas[${indice}][porcentaje_iva]`" x-model="linea.porcentaje_iva"
                                    :disabled="!producto(linea.producto_id)"
                                    class="campo tnum" :class="[error(indice, 'porcentaje_iva') && 'campo-error', ivaManual(linea) && 'border-oro/60']">
                                    @foreach (config('contable.tarifas_iva') as $tarifa)
                                        <option value="{{ $tarifa }}">{{ $tarifa }}%</option>
                                    @endforeach
                                </select>
                                <span x-show="montos(linea).iva > 0" class="mt-1 block text-right text-xs text-niebla" x-text="dinero(montos(linea).iva)"></span>
                                <p x-show="error(indice, 'porcentaje_iva')" x-text="error(indice, 'porcentaje_iva')" class="mt-1 text-xs text-cobre" role="alert" x-cloak></p>
                            </td>
                            <td class="tnum pt-5 text-right font-semibold" x-text="dinero(montos(linea).total)"></td>
                            <td class="pt-4 text-right">
                                <button type="button" x-on:click="quitar(indice)"
                                    class="grid size-9 place-items-center rounded-lg text-niebla hover:bg-cobre/10 hover:text-cobre"
                                    :aria-label="'Quitar la línea ' + (indice + 1)">
                                    <x-ui.icon name="cerrar" class="size-4" />
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="px-5 py-3 text-xs text-niebla">El precio unitario (sin IVA), el % de descuento y la tarifa de IVA se cargan del catálogo y pueden ajustarse en cada línea; quedan guardados en el documento.</p>
    @endif
</section>
