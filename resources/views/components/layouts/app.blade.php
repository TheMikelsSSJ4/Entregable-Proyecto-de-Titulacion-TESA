@props(['title', 'heading' => null])

@php
    $usuario = auth()->user();
    $modulos = [
        ['ruta' => 'dashboard', 'activo' => 'dashboard', 'icono' => 'panel', 'texto' => 'Dashboard', 'visible' => true],
        ['ruta' => 'usuarios.index', 'activo' => 'usuarios.*', 'icono' => 'escudo', 'texto' => 'Seguridad y Usuarios', 'visible' => $usuario->can('viewAny', \App\Models\Usuario::class)],
        ['ruta' => 'clientes.index', 'activo' => 'clientes.*', 'icono' => 'edificio', 'texto' => 'Clientes', 'visible' => true],
        ['ruta' => 'productos.index', 'activo' => 'productos.*', 'icono' => 'caja', 'texto' => 'Productos', 'visible' => true],
        ['ruta' => 'facturas.index', 'activo' => ['facturas.*', 'abonos.*'], 'icono' => 'flujo', 'texto' => 'Transacciones', 'visible' => true],
        ['ruta' => 'documentos.index', 'activo' => ['documentos.*', 'cotizaciones.*'], 'icono' => 'documento', 'texto' => 'Documentos', 'visible' => true],
        ['ruta' => 'cartera.index', 'activo' => 'cartera.*', 'icono' => 'radar', 'texto' => 'Cartera y Alertas', 'visible' => $usuario->can('ver-cartera')],
        ['ruta' => 'reportes.index', 'activo' => 'reportes.*', 'icono' => 'grafico', 'texto' => 'Reportes', 'visible' => $usuario->can('ver-reportes')],
        ['ruta' => 'empresa.edit', 'activo' => 'empresa.*', 'icono' => 'ajustes', 'texto' => 'Configuración', 'visible' => $usuario->can('configurar-empresa')],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" data-tema="{{ $temaActual }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="{{ $temaActual === 'claro' ? 'light' : 'dark' }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#contenido" class="sr-only z-50 rounded-lg bg-menta px-4 py-2 font-semibold text-obsidiana focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Saltar al contenido</a>

    {{--
        Menú lateral de módulos.
        - Escritorio, modo desplegable (por defecto): franja de íconos que se expande al
          acercar el mouse o al navegar con Tab, y se repliega al salir.
        - Escritorio, modo fijo: siempre abierto. La preferencia se guarda en el navegador.
        - Celular / tablet: se abre con el botón ☰.
    --}}
    <div x-data="menuLateral" data-menu-fijo="false" x-bind:data-menu-fijo="fijo.toString()" class="group/app flex min-h-dvh">
        <div id="menu-fondo" class="fixed inset-0 z-30 bg-obsidiana/70 backdrop-blur-sm lg:hidden" hidden></div>

        <aside id="menu-lateral" data-abierto="false" data-expandido="false" x-bind:data-expandido="expandido.toString()"
            x-on:mouseenter="entrar()" x-on:mouseleave="salir()"
            x-on:focusin="alEnfocar()" x-on:focusout="alDesenfocar($event, $el)"
            class="group/menu fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col overflow-x-hidden border-r border-tinta/[0.06] bg-menu/95 backdrop-blur-xl transition-[transform,width,box-shadow] duration-200 data-[abierto=true]:translate-x-0 lg:w-[4.5rem] lg:translate-x-0 lg:data-[expandido=true]:w-64 lg:group-data-[menu-fijo=false]/app:data-[expandido=true]:shadow-[24px_0_48px_-12px_var(--sombra-menu)]">
            <div class="flex items-center justify-between gap-3 px-4 pt-6 pb-8">
                <a href="{{ route('dashboard') }}" class="rounded-xl" aria-label="Privileg Contable, ir al Dashboard">
                    <x-ui.logo class="[&>span:last-child]:transition-opacity [&>span:last-child]:duration-150 lg:group-data-[expandido=false]/menu:[&>span:last-child]:opacity-0" />
                </a>
                <button type="button" data-menu-toggle class="grid size-9 place-items-center rounded-lg text-niebla hover:bg-tinta/[0.06] hover:text-platino lg:hidden" aria-label="Cerrar menú" aria-controls="menu-lateral">
                    <x-ui.icon name="cerrar" />
                </button>
            </div>

            <nav aria-label="Módulos del sistema" class="flex-1 overflow-x-hidden overflow-y-auto px-3">
                <p class="eyebrow px-3 pb-3 whitespace-nowrap text-niebla/80 transition-opacity duration-150 lg:group-data-[expandido=false]/menu:opacity-0">Módulos</p>
                <ul class="flex flex-col gap-1">
                    @foreach ($modulos as $modulo)
                        @continue(! $modulo['visible'])
                        @php $esActual = request()->routeIs($modulo['activo']); @endphp
                        <li>
                            <a href="{{ route($modulo['ruta']) }}"
                               @if ($esActual) aria-current="page" @endif
                               class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150 {{ $esActual ? 'bg-menta/[0.09] text-platino' : 'text-niebla hover:bg-tinta/[0.04] hover:text-platino' }}">
                                @if ($esActual)
                                    <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-menta shadow-[0_0_12px_rgb(0_245_160/0.8)]" aria-hidden="true"></span>
                                @endif
                                <x-ui.icon :name="$modulo['icono']" class="size-[1.15rem] {{ $esActual ? 'text-menta' : 'text-niebla group-hover:text-platino' }}" />
                                <span class="whitespace-nowrap transition-opacity duration-150 lg:group-data-[expandido=false]/menu:opacity-0">{{ $modulo['texto'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Modo del menú (sólo escritorio) --}}
            <div class="hidden px-3 pt-3 lg:block">
                <button type="button" x-on:click="alternarFijo()"
                    x-bind:aria-pressed="fijo.toString()"
                    aria-pressed="false"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-niebla transition-colors hover:bg-tinta/[0.04] hover:text-platino">
                    <x-ui.icon name="chincheta" class="size-[1.15rem]" x-bind:class="fijo ? 'text-menta' : 'text-niebla'" />
                    <span class="whitespace-nowrap transition-opacity duration-150 lg:group-data-[expandido=false]/menu:opacity-0">
                        Menú: <span x-text="fijo ? 'Fijo' : 'Desplegable'">Desplegable</span>
                    </span>
                    <span class="sr-only" x-text="fijo ? '(pulse para que se oculte solo)' : '(pulse para dejarlo siempre abierto)'"></span>
                </button>
            </div>

            <div class="m-3 rounded-xl border border-tinta/[0.06] bg-tinta/[0.02] p-2.5 transition-colors lg:group-data-[expandido=false]/menu:border-transparent lg:group-data-[expandido=false]/menu:bg-transparent">
                <div class="flex items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-violeta/20 font-display text-sm font-bold text-lila" aria-hidden="true" title="{{ $usuario->nombre_completo }}">{{ $usuario->iniciales() }}</span>
                    <div class="min-w-0 transition-opacity duration-150 lg:group-data-[expandido=false]/menu:opacity-0">
                        <p class="truncate text-sm font-semibold text-platino" title="{{ $usuario->nombre_completo }}">{{ $usuario->nombre_completo }}</p>
                        <p class="truncate text-xs text-niebla">{{ $usuario->rol->nombre->value }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-sm font-semibold text-niebla transition-colors hover:bg-tinta/[0.06] hover:text-platino">
                        <x-ui.icon name="salir" class="size-[1.15rem] shrink-0" />
                        <span class="whitespace-nowrap transition-opacity duration-150 lg:group-data-[expandido=false]/menu:opacity-0">Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Área principal: deja espacio a la franja (desplegable) o al menú completo (fijo) --}}
        <div class="flex min-w-0 flex-1 flex-col transition-[padding] duration-200 lg:pl-[4.5rem] lg:group-data-[menu-fijo=true]/app:pl-64">
            <header class="sticky top-0 z-20 border-b border-tinta/[0.06] bg-obsidiana/80 backdrop-blur-xl">
                <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                    <button type="button" data-menu-toggle aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menú" class="grid size-10 place-items-center rounded-xl border border-tinta/10 text-platino hover:bg-tinta/[0.06] lg:hidden">
                        <x-ui.icon name="menu" />
                    </button>
                    <div class="min-w-0 flex-1">
                        <h1 class="truncate font-display text-lg font-bold tracking-tight text-platino sm:text-xl">{{ $heading ?? $title }}</h1>
                    </div>
                    <div class="hidden items-center gap-2 text-sm text-niebla sm:flex">
                        <x-ui.icon name="calendario" class="size-4" />
                        <span class="tnum">{{ now()->translatedFormat('d M Y') }}</span>
                    </div>
                    <x-ui.badge tone="violeta" class="hidden md:inline-flex">{{ $usuario->rol->nombre->value }}</x-ui.badge>
                    <x-ui.tema />
                </div>
            </header>

            <main id="contenido" tabindex="-1" class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 focus:outline-none sm:px-6 lg:px-8">
                <x-ui.flash />
                {{ $slot }}
            </main>

            <footer class="mx-auto w-full max-w-7xl px-4 pb-6 text-xs text-niebla/80 sm:px-6 lg:px-8">
                {{ $empresaActual->razon_social }} · RUC {{ $empresaActual->ruc }} · Documentos internos de control (no reemplazan comprobantes del SRI)
            </footer>
        </div>
    </div>

    {{-- Diálogo de confirmación para acciones irreversibles --}}
    <dialog id="dialogo-confirmacion" aria-labelledby="dialogo-titulo" class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-2xl border border-tinta/10 bg-grafito p-0 text-platino shadow-2xl">
        <form method="dialog" class="flex flex-col gap-5 p-6">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-cobre/10 text-cobre"><x-ui.icon name="alerta" /></span>
                <div>
                    <h2 id="dialogo-titulo" class="font-display text-lg font-semibold">¿Confirmar acción?</h2>
                    <p data-confirm-mensaje class="mt-1 text-sm text-niebla"></p>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button type="submit" value="cancelar" variant="secondary">Cancelar</x-ui.button>
                <x-ui.button type="submit" value="aceptar" variant="danger" data-confirm-aceptar>Confirmar</x-ui.button>
            </div>
        </form>
    </dialog>
</body>
</html>
