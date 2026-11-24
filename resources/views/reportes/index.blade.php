<x-layouts.app title="Reportes" heading="Reportes avanzados y exportaciones">
    @php
        $atajos = [
            'Este mes' => [now()->startOfMonth()->toDateString(), today()->toDateString()],
            'Mes anterior' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'Últimos 90 días' => [today()->subDays(90)->toDateString(), today()->toDateString()],
            'Año actual' => [now()->startOfYear()->toDateString(), today()->toDateString()],
        ];

        $general = ['tipo' => 'general', 'icono' => 'panel', 'tono' => 'text-oro', 'titulo' => 'Reporte general del periodo', 'corto' => 'General', 'texto' => 'Todo el periodo en un solo documento: ventas y cobros, ventas por vendedor y cartera por cobrar con antigüedad de saldos y principales deudores.'];

        $reportes = [
            ['tipo' => 'ventas', 'icono' => 'grafico', 'tono' => 'text-cian', 'titulo' => 'Ventas y transacciones', 'corto' => 'Ventas', 'texto' => 'Facturas emitidas en el periodo (contado y crédito), descuentos, IVA y cobros. Si elige un vendedor, sólo incluye sus ventas.'],
            ['tipo' => 'vendedores', 'icono' => 'usuario', 'tono' => 'text-menta', 'titulo' => 'Ventas por vendedor', 'corto' => 'Por vendedor', 'texto' => 'Total vendido, contado, crédito, cobrado, saldo y mora de cada vendedor, con su participación y el detalle de sus facturas.'],
            ['tipo' => 'cartera', 'icono' => 'radar', 'tono' => 'text-cobre', 'titulo' => 'Cartera por cobrar', 'corto' => 'Cartera', 'texto' => 'Facturas pendientes y vencidas emitidas en el periodo, con días de mora y antigüedad de saldos.'],
            ['tipo' => 'estado-cuenta', 'icono' => 'edificio', 'tono' => 'text-lila', 'titulo' => 'Estado de cuenta por cliente', 'corto' => 'Estado de cuenta', 'texto' => 'Facturas y abonos del cliente en el periodo, con su saldo y mora actuales. Requiere elegir un cliente.'],
        ];

        $todos = [$general, ...$reportes];

        // Descarga directa y vista previa (PDF o Excel) de cada reporte.
        $urlsDescarga = collect($todos)->mapWithKeys(fn (array $reporte): array => [$reporte['tipo'] => [
            'pdf' => route('reportes.exportar', ['tipo' => $reporte['tipo'], 'formato' => 'pdf']),
            'xlsx' => route('reportes.exportar', ['tipo' => $reporte['tipo'], 'formato' => 'xlsx']),
            'vista' => [
                'pdf' => route('reportes.vista-previa', ['tipo' => $reporte['tipo'], 'formato' => 'pdf']),
                'xlsx' => route('reportes.vista-previa', ['tipo' => $reporte['tipo'], 'formato' => 'xlsx']),
            ],
        ]])->all();
    @endphp

    <x-ui.page-header eyebrow="PDF · Excel (.xlsx)" tone="cian"
        title="Genere reportes para el periodo que necesite"
        description="Todos los reportes llevan en su cabecera los datos de la empresa registrados en Configuración. Use «Vista previa» para revisarlos antes de descargar. Son documentos internos de control de cartera y ventas." />

    <form method="GET" action="{{ route('reportes.index') }}" class="flex flex-col gap-6" novalidate x-data="vistaPreviaReporte(@js($urlsDescarga))">
        <section class="card flex flex-col gap-5 p-5 sm:p-6" aria-labelledby="titulo-filtros">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="eyebrow text-menta">Filtros</p>
                    <h2 id="titulo-filtros" class="mt-1 text-base font-semibold">Periodo del reporte</h2>
                </div>
                <nav aria-label="Periodos rápidos" class="flex flex-wrap gap-1.5">
                    @foreach ($atajos as $etiqueta => [$inicio, $fin])
                        @php $activo = $inicio === $fechaInicio && $fin === $fechaFin; @endphp
                        <a href="{{ route('reportes.index', array_filter(['fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'cliente_id' => $clienteId, 'vendedor_id' => $vendedorId])) }}"
                           @if ($activo) aria-current="true" @endif
                           class="rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors {{ $activo ? 'border-menta/60 text-menta' : 'border-tinta/10 text-niebla hover:text-platino' }}">{{ $etiqueta }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 md:items-end xl:grid-cols-[11rem_11rem_1fr_1fr_auto_auto]">
                <x-ui.input label="Fecha de inicio" name="fecha_inicio" type="date" :value="$fechaInicio" required max="{{ today()->toDateString() }}" />
                <x-ui.input label="Fecha de fin" name="fecha_fin" type="date" :value="$fechaFin" required />
                <x-ui.select label="Cliente (estado de cuenta)" name="cliente_id" :value="$clienteId" placeholder="Todos / seleccione para estado de cuenta"
                    :options="$clientes->mapWithKeys(fn ($c) => [$c->id => $c->razon_social.' · '.$c->ruc_cedula])->all()" />
                <x-ui.select label="Vendedor (reporte de ventas)" name="vendedor_id" :value="$vendedorId" placeholder="Todos los vendedores"
                    :options="$vendedores" />
                <x-ui.button type="submit" variant="secondary" icon="buscar">Actualizar</x-ui.button>
                {{-- Vista previa general: abre la ventana en "General" y desde ahí se pasa a cualquier reporte. --}}
                <x-ui.button type="submit" icon="ojo"
                    formaction="{{ $urlsDescarga[$general['tipo']]['vista']['pdf'] }}"
                    formtarget="marco-vista-previa"
                    data-tipo="{{ $general['tipo'] }}" data-titulo="{{ $general['titulo'] }}"
                    x-on:click="abrir($el.dataset.tipo, $el.dataset.titulo)"
                    aria-haspopup="dialog" data-vista-previa-general>Vista previa</x-ui.button>
            </div>
        </section>

        @if ($vistaPrevia)
            <section aria-labelledby="titulo-vista-previa" class="flex flex-col gap-3">
                <h2 id="titulo-vista-previa" class="eyebrow text-niebla">Resumen del periodo · {{ \Illuminate\Support\Carbon::parse($fechaInicio)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($fechaFin)->format('d/m/Y') }}@if ($vendedorId && isset($vendedores[$vendedorId])) · {{ \Illuminate\Support\Str::before($vendedores[$vendedorId], ' — ') }}@endif</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-ui.kpi label="Ventas del periodo" icon="flujo">
                        <x-ui.money :value="$vistaPrevia['total']" />
                        <x-slot:pie>{{ $vistaPrevia['cantidad'] }} facturas · IVA <x-ui.money :value="$vistaPrevia['iva']" /></x-slot:pie>
                    </x-ui.kpi>
                    <x-ui.kpi label="Contado / crédito" tone="violeta" icon="billete">
                        <x-ui.money :value="$vistaPrevia['credito']" />
                        <x-slot:pie>A crédito · contado <x-ui.money :value="$vistaPrevia['contado']" /></x-slot:pie>
                    </x-ui.kpi>
                    <x-ui.kpi label="Cobrado" tone="menta" icon="subida">
                        <x-ui.money :value="$vistaPrevia['cobrado']" />
                        <x-slot:pie>Pagos y abonos recibidos en el periodo</x-slot:pie>
                    </x-ui.kpi>
                    <x-ui.kpi label="Saldo por cobrar" tone="cobre" icon="reloj">
                        <x-ui.money :value="$vistaPrevia['saldo']" />
                        <x-slot:pie>De las facturas emitidas en el periodo</x-slot:pie>
                    </x-ui.kpi>
                </div>
            </section>
        @endif

        <section aria-labelledby="titulo-tipos" class="flex flex-col gap-3">
            <h2 id="titulo-tipos" class="eyebrow text-niebla">Exportar</h2>

            {{-- Reporte general: tarjeta destacada --}}
            <article class="card flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-oro/10 {{ $general['tono'] }}"><x-ui.icon :name="$general['icono']" /></span>
                    <div>
                        <h3 class="font-display text-base font-semibold">{{ $general['titulo'] }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-niebla">{{ $general['texto'] }}</p>
                    </div>
                </div>
                <div class="grid shrink-0 grid-cols-2 gap-2 sm:grid-cols-3 lg:w-[26rem]">
                    <x-ui.button type="submit" variant="secondary" icon="ojo" class="col-span-2 sm:col-span-1"
                        formaction="{{ $urlsDescarga[$general['tipo']]['vista']['pdf'] }}"
                        formtarget="marco-vista-previa"
                        data-tipo="{{ $general['tipo'] }}" data-titulo="{{ $general['titulo'] }}"
                        x-on:click="abrir($el.dataset.tipo, $el.dataset.titulo)"
                        aria-haspopup="dialog" data-vista-previa-reporte
                        aria-label="Vista previa de {{ $general['titulo'] }}">Vista previa</x-ui.button>
                    <x-ui.button type="submit" variant="danger" icon="descarga"
                        formaction="{{ $urlsDescarga[$general['tipo']]['pdf'] }}"
                        aria-label="Descargar {{ $general['titulo'] }} en PDF">PDF</x-ui.button>
                    <x-ui.button type="submit" icon="excel"
                        formaction="{{ $urlsDescarga[$general['tipo']]['xlsx'] }}"
                        aria-label="Descargar {{ $general['titulo'] }} en Excel">Excel</x-ui.button>
                </div>
            </article>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-4">
                @foreach ($reportes as $reporte)
                    <article class="card flex flex-col gap-4 p-5">
                        <div class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-tinta/[0.05] {{ $reporte['tono'] }}"><x-ui.icon :name="$reporte['icono']" /></span>
                            <div>
                                <h3 class="font-display text-base font-semibold">{{ $reporte['titulo'] }}</h3>
                                <p class="mt-1 text-sm text-niebla">{{ $reporte['texto'] }}</p>
                            </div>
                        </div>
                        <div class="mt-auto grid grid-cols-2 gap-2">
                            <x-ui.button type="submit" variant="secondary" icon="ojo" class="col-span-2"
                                formaction="{{ $urlsDescarga[$reporte['tipo']]['vista']['pdf'] }}"
                                formtarget="marco-vista-previa"
                                data-tipo="{{ $reporte['tipo'] }}" data-titulo="{{ $reporte['titulo'] }}"
                                x-on:click="abrir($el.dataset.tipo, $el.dataset.titulo)"
                                aria-haspopup="dialog" data-vista-previa-reporte
                                aria-label="Vista previa de {{ $reporte['titulo'] }}">Vista previa</x-ui.button>
                            <x-ui.button type="submit" variant="danger" icon="descarga"
                                formaction="{{ $urlsDescarga[$reporte['tipo']]['pdf'] }}"
                                aria-label="Descargar {{ $reporte['titulo'] }} en PDF">PDF</x-ui.button>
                            <x-ui.button type="submit" icon="excel"
                                formaction="{{ $urlsDescarga[$reporte['tipo']]['xlsx'] }}"
                                aria-label="Descargar {{ $reporte['titulo'] }} en Excel">Excel</x-ui.button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{--
            Ventana de vista previa. Cada pestaña envía los filtros actuales al marco
            (formtarget) con su propio reporte; también se puede descargar desde aquí.
        --}}
        <dialog x-ref="dialogo" x-on:close="alCerrar()" aria-labelledby="titulo-dialogo-previa"
            class="m-auto h-[calc(100dvh-2rem)] max-h-none w-[min(84rem,calc(100vw-2rem))] max-w-none flex-col overflow-hidden rounded-2xl border border-tinta/10 bg-grafito p-0 text-platino shadow-2xl open:flex">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-tinta/[0.06] px-5 py-4">
                <div class="min-w-0">
                    <p class="eyebrow text-cian">Vista previa</p>
                    <h2 id="titulo-dialogo-previa" class="mt-1 truncate text-base font-semibold" x-text="titulo"></h2>
                    <p class="mt-0.5 text-xs text-niebla" x-text="formato === 'xlsx' ? 'Así se verá el Excel: cada hoja del libro, con sus montos en formato de moneda.' : 'Así se verá el PDF: hoja A4 lista para imprimir.'">Así se verá el PDF: hoja A4 lista para imprimir.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.button type="submit" variant="danger" size="sm" icon="descarga" x-bind:formaction="urls[tipo]?.pdf">Descargar PDF</x-ui.button>
                    <x-ui.button type="submit" size="sm" icon="excel" x-bind:formaction="urls[tipo]?.xlsx">Descargar Excel</x-ui.button>
                    <x-ui.button variant="ghost" size="sm" icon="cerrar" x-on:click="cerrar()">Cerrar</x-ui.button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-tinta/[0.06] px-4 py-2">
            <div class="flex min-w-0 gap-1 overflow-x-auto" role="group" aria-label="Reporte que se muestra">
                @foreach ($todos as $reporte)
                    <button type="submit"
                        formaction="{{ $urlsDescarga[$reporte['tipo']]['vista']['pdf'] }}"
                        x-bind:formaction="urls[$el.dataset.tipo].vista[formato]"
                        formtarget="marco-vista-previa"
                        data-tipo="{{ $reporte['tipo'] }}" data-titulo="{{ $reporte['titulo'] }}"
                        x-on:click="seleccionar($el.dataset.tipo, $el.dataset.titulo)"
                        aria-pressed="false" x-bind:aria-pressed="(tipo === $el.dataset.tipo).toString()"
                        x-bind:class="tipo === $el.dataset.tipo ? 'bg-menta/15 text-menta' : 'text-niebla hover:bg-tinta/[0.04] hover:text-platino'"
                        @if ($reporte['tipo'] === 'estado-cuenta') title="Requiere elegir un cliente en los filtros" @endif
                        class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-semibold whitespace-nowrap transition-colors"
                        data-pestana-vista-previa>{{ $reporte['corto'] }}</button>
                @endforeach
            </div>

            {{-- Formato de la vista previa: cómo queda el PDF o cómo queda el Excel --}}
            <div class="flex shrink-0 items-center gap-1 rounded-xl border border-tinta/10 p-1" role="group" aria-label="Formato de la vista previa">
                @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel'] as $formato => $etiqueta)
                    <button type="submit"
                        formaction="{{ $urlsDescarga[$general['tipo']]['vista'][$formato] }}"
                        x-bind:formaction="urls[tipo ?? 'general'].vista[$el.dataset.formato]"
                        formtarget="marco-vista-previa"
                        data-formato="{{ $formato }}"
                        x-on:click="cambiarFormato($el.dataset.formato)"
                        aria-pressed="{{ $formato === 'pdf' ? 'true' : 'false' }}" x-bind:aria-pressed="(formato === $el.dataset.formato).toString()"
                        x-bind:class="formato === $el.dataset.formato ? 'bg-grafito-alto text-platino shadow-[inset_0_0_0_1px_var(--color-menta)]' : 'text-niebla hover:text-platino'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-semibold transition-colors"
                        data-formato-vista-previa>
                        <x-ui.icon :name="$formato === 'pdf' ? 'documento' : 'excel'" class="size-3.5" />{{ $etiqueta }}
                    </button>
                @endforeach
            </div>
            </div>

            <div class="relative flex-1 bg-grafito-alto">
                <div x-show="cargando" class="absolute inset-0 z-10 grid place-items-center bg-grafito-alto text-sm text-niebla" role="status">Generando vista previa…</div>
                <iframe x-ref="marco" name="marco-vista-previa" title="Vista previa del reporte" sandbox="allow-same-origin"
                    class="size-full" x-on:load="cargando = false"></iframe>
            </div>
        </dialog>
    </form>
</x-layouts.app>
