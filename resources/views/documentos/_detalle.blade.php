{{-- Tabla de líneas de un documento emitido. Requiere $documento (Factura o Cotizacion con detalles cargados). --}}
<section class="card min-w-0" aria-labelledby="titulo-detalle">
    <h2 id="titulo-detalle" class="px-5 pt-5 pb-3 text-base font-semibold">Detalle <span class="tnum text-niebla">({{ $documento->detalles->count() }} {{ $documento->detalles->count() === 1 ? 'línea' : 'líneas' }})</span></h2>
    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="text-right">Cantidad</th>
                    <th scope="col" class="text-right">P. unitario</th>
                    <th scope="col" class="text-right">Descuento</th>
                    <th scope="col" class="text-right">Subtotal</th>
                    <th scope="col" class="text-right">IVA</th>
                    <th scope="col" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documento->detalles as $detalle)
                    <tr>
                        <td class="max-w-80"><span class="block truncate font-medium" title="{{ $detalle->descripcion }}">{{ $detalle->descripcion }}</span></td>
                        <td class="tnum text-right">{{ rtrim(rtrim($detalle->cantidad, '0'), '.') }}</td>
                        <td class="text-right"><x-ui.money :value="$detalle->precio_unitario" /></td>
                        <td class="text-right text-niebla">
                            @if ((float) $detalle->descuento > 0)
                                <x-ui.money :value="$detalle->descuento" sign="-" class="text-cobre" />
                                <span class="block text-xs">{{ rtrim(rtrim($detalle->porcentaje_descuento, '0'), '.') }}%</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right"><x-ui.money :value="$detalle->subtotal" /></td>
                        <td class="text-right text-niebla"><x-ui.money :value="$detalle->iva" /><span class="block text-xs">{{ (int) $detalle->porcentaje_iva }}%</span></td>
                        <td class="text-right font-semibold"><x-ui.money :value="$detalle->total" /></td>
                    </tr>
                @empty
                    <x-ui.empty :colspan="7" icon="caja" title="Documento sin líneas de detalle">Registrado antes del catálogo de productos.</x-ui.empty>
                @endforelse
            </tbody>
            <tfoot class="text-sm">
                <tr><td colspan="6" class="px-4 pt-4 text-right text-niebla">Subtotal (base imponible)</td><td class="px-4 pt-4 text-right"><x-ui.money :value="$documento->subtotal" /></td></tr>
                @if ((float) $documento->descuento > 0)
                    <tr><td colspan="6" class="px-4 pt-1 text-right text-niebla">Descuentos aplicados</td><td class="px-4 pt-1 text-right text-cobre"><x-ui.money :value="$documento->descuento" sign="-" /></td></tr>
                @endif
                <tr><td colspan="6" class="px-4 pt-1 text-right text-niebla">IVA</td><td class="px-4 pt-1 text-right"><x-ui.money :value="$documento->iva" /></td></tr>
                <tr><td colspan="6" class="px-4 pt-2 pb-4 text-right font-semibold">Total</td><td class="px-4 pt-2 pb-4 text-right font-display text-lg font-bold text-menta"><x-ui.money :value="$documento->total" /></td></tr>
            </tfoot>
        </table>
    </div>
</section>
