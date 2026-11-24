<x-layouts.app title="Seguridad y Usuarios" heading="Seguridad y gestión de usuarios">
    <x-ui.page-header eyebrow="Control de accesos" tone="violeta"
        title="Usuarios y roles del sistema"
        description="Administre las cuentas de Gerentes, Contadores y Vendedores. Las cuentas no se eliminan: se desactivan para conservar la trazabilidad de facturas y abonos.">
        <x-slot:actions>
            <x-ui.button :href="route('usuarios.create')" icon="mas">Nuevo usuario</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="card min-w-0" aria-labelledby="titulo-usuarios">
        <h2 id="titulo-usuarios" class="px-5 pt-5 pb-3 text-base font-semibold">Usuarios registrados <span class="tnum text-niebla">({{ $usuarios->total() }})</span></h2>
        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Nombre completo</th>
                        <th scope="col">Correo electrónico</th>
                        <th scope="col">Rol asignado</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-violeta/15 font-display text-xs font-bold text-lila" aria-hidden="true">{{ $usuario->iniciales() }}</span>
                                    <span class="font-medium">{{ $usuario->nombre_completo }}</span>
                                    @if ($usuario->is(auth()->user()))<x-ui.badge tone="cian" :dot="false">Usted</x-ui.badge>@endif
                                </div>
                            </td>
                            <td class="max-w-64 truncate text-niebla" title="{{ $usuario->email }}">{{ $usuario->email }}</td>
                            <td>
                                <span class="font-medium">{{ $usuario->rol->nombre->value }}</span>
                                <span class="block max-w-64 truncate text-xs text-niebla">{{ $usuario->rol->nombre->descripcion() }}</span>
                            </td>
                            <td><x-ui.badge :tone="$usuario->estado->tono()">{{ $usuario->estado->etiqueta() }}</x-ui.badge></td>
                            <td class="text-right">
                                <x-ui.button :href="route('usuarios.edit', $usuario)" variant="ghost" size="sm" icon="editar" aria-label="Editar a {{ $usuario->nombre_completo }}">Editar</x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty :colspan="5" icon="usuario" title="No hay usuarios registrados" />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $usuarios->links() }}</div>
    </section>
</x-layouts.app>
