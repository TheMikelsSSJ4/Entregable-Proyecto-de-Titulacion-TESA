<x-layouts.app title="Productos" heading="Catálogo de productos y servicios">
    @php $puedeEditar = auth()->user()->can('create', \App\Models\Producto::class); @endphp

    <x-ui.page-header eyebrow="Catálogo de facturación" tone="cian"
        title="Productos y servicios facturables"
        description="Precios base, descuentos e IVA que usan las cotizaciones y facturas. No incluye control de stock ni inventario físico.">
        @if ($puedeEditar)
            <x-slot:actions>
                <x-ui.button :href="route('productos.create')" icon="mas">Nuevo producto</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <section class="card min-w-0" aria-labelledby="titulo-productos">
        <div class="flex flex-col gap-3 px-5 pt-5 pb-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 id="titulo-productos" class="text-base font-semibold">Catálogo <span class="tnum text-niebla">({{ $productos->total() }})</span></h2>
            <form method="GET" action="{{ route('productos.index') }}" role="search" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_10rem_auto] lg:w-[34rem]">
                <div class="relative">
                    <label for="buscar" class="sr-only">Buscar por código o nombre</label>
                    <x-ui.icon name="buscar" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-niebla" />
                    <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Código o nombre" class="campo pl-9" maxlength="100">
                </div>
                <div>
                    <label for="estado" class="sr-only">Estado</label>
                    <select id="estado" name="estado" class="campo" data-autoenvio>
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? '') === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Producto / servicio</th>
                        <th scope="col" class="text-right">Precio base</th>
                        <th scope="col" class="text-right">Desc.</th>
                        <th scope="col" class="text-right">IVA</th>
                        <th scope="col" class="text-right">Precio final</th>
                        <th scope="col">Estado</th>
                        @if ($puedeEditar)<th scope="col"><span class="sr-only">Acciones</span></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr @class(['opacity-60' => ! $producto->activo])>
                            <td class="tnum font-medium text-niebla">{{ $producto->codigo }}</td>
                            <td class="max-w-80"><span class="block truncate font-semibold" title="{{ $producto->nombre }}">{{ $producto->nombre }}</span></td>
                            <td class="text-right"><x-ui.money :value="$producto->precio_base" /></td>
                            <td class="tnum text-right text-niebla">{{ (float) $producto->porcentaje_descuento > 0 ? rtrim(rtrim($producto->porcentaje_descuento, '0'), '.').'%' : '—' }}</td>
                            <td class="tnum text-right text-niebla">{{ (int) $producto->porcentaje_iva }}%</td>
                            <td class="text-right font-display font-bold text-menta"><x-ui.money :value="$producto->precio_final" /></td>
                            <td><x-ui.badge :tone="$producto->activo ? 'menta' : 'niebla'">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</x-ui.badge></td>
                            @if ($puedeEditar)
                                <td class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('productos.edit', $producto)" variant="ghost" size="sm" icon="editar" aria-label="Editar {{ $producto->codigo }}">Editar</x-ui.button>
                                        <form method="POST" action="{{ route('productos.destroy', $producto) }}"
                                              data-confirm="Se eliminará el producto {{ $producto->codigo }}. Si ya figura en cotizaciones o facturas no podrá eliminarse; en ese caso desactívelo."
                                              data-confirm-boton="Eliminar producto">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" icon="basura" aria-label="Eliminar {{ $producto->codigo }}"><span class="sr-only">Eliminar</span></x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <x-ui.empty :colspan="$puedeEditar ? 8 : 7" icon="caja" :title="array_filter($filtros) ? 'Ningún producto coincide con la búsqueda' : 'El catálogo está vacío'">
                            @if (array_filter($filtros))
                                <a href="{{ route('productos.index') }}" class="text-menta underline underline-offset-4">Quitar filtros</a>
                            @else
                                Registre el primer producto con «Nuevo producto».
                            @endif
                        </x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $productos->links() }}</div>
    </section>
</x-layouts.app>
