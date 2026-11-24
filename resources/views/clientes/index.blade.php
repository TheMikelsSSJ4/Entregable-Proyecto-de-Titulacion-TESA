<x-layouts.app title="Clientes" heading="Directorio de clientes y cuentas por cobrar">
    <x-ui.page-header eyebrow="Módulo de clientes"
        title="Gestión de clientes registrados"
        description="Directorio con el saldo pendiente de cada cliente y su condición de mora.">
        <x-slot:actions>
            <x-ui.button :href="route('clientes.create')" icon="mas">Nuevo cliente</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="card min-w-0" aria-labelledby="titulo-clientes">
        <div class="flex flex-col gap-3 px-5 pt-5 pb-3 md:flex-row md:items-center md:justify-between">
            <h2 id="titulo-clientes" class="text-base font-semibold">Listado general <span class="tnum text-niebla">({{ $clientes->total() }})</span></h2>
            <form method="GET" action="{{ route('clientes.index') }}" role="search" class="flex w-full gap-2 md:w-96">
                <label for="buscar" class="sr-only">Buscar cliente por razón social o RUC/cédula</label>
                <div class="relative flex-1">
                    <x-ui.icon name="buscar" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-niebla" />
                    <input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Razón social o RUC/cédula" class="campo pl-9" maxlength="100">
                </div>
                <x-ui.button type="submit" variant="secondary">Buscar</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">RUC / Cédula</th>
                        <th scope="col">Razón social</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col" class="text-right">Saldo pendiente</th>
                        <th scope="col">Condición</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        @php
                            $saldo = (float) $cliente->saldo_pendiente;
                            $vencimiento = $cliente->vencimiento_mas_antiguo ? \Illuminate\Support\Carbon::parse($cliente->vencimiento_mas_antiguo) : null;
                            $enMora = $saldo > 0 && $vencimiento && $vencimiento->lt(today());
                        @endphp
                        <tr>
                            <td class="tnum text-niebla">{{ $cliente->ruc_cedula }}</td>
                            <td class="max-w-72"><a href="{{ route('clientes.show', $cliente) }}" class="block truncate font-semibold hover:text-menta" title="{{ $cliente->razon_social }}">{{ $cliente->razon_social }}</a></td>
                            <td class="tnum text-niebla">{{ $cliente->telefono ?? '—' }}</td>
                            <td class="text-right font-semibold {{ $saldo > 0 ? '' : 'text-niebla' }}"><x-ui.money :value="$saldo" /></td>
                            <td>
                                @if ($enMora)
                                    <x-ui.badge tone="cobre">En mora</x-ui.badge>
                                @elseif ($saldo > 0)
                                    <x-ui.badge tone="oro">Con saldo</x-ui.badge>
                                @else
                                    <x-ui.badge tone="menta">Al día</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-ui.button :href="route('clientes.show', $cliente)" variant="ghost" size="sm" icon="ojo" aria-label="Ver detalle de {{ $cliente->razon_social }}">Ver detalle</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="6" icon="edificio" :title="$buscar !== '' ? 'Ningún cliente coincide con «'.$buscar.'»' : 'Aún no hay clientes registrados'">
                            @if ($buscar !== '')
                                <a href="{{ route('clientes.index') }}" class="text-menta underline underline-offset-4">Limpiar la búsqueda</a>
                            @else
                                Registre el primer cliente con «Nuevo cliente».
                            @endif
                        </x-ui.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $clientes->links() }}</div>
    </section>
</x-layouts.app>
