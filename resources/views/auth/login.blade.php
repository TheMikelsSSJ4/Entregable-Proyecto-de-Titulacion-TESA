<x-layouts.guest title="Iniciar sesión">
    <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-tinta/[0.06] bg-grafito/70 shadow-2xl backdrop-blur-xl lg:grid-cols-[1.1fr_1fr]">
        {{-- Panel de marca --}}
        <section class="relative hidden flex-col justify-between gap-10 overflow-hidden border-r border-tinta/[0.06] bg-gradient-to-br from-menta/[0.08] via-transparent to-violeta/[0.12] p-10 lg:flex" aria-hidden="true">
            <x-ui.logo />
            <div>
                <p class="eyebrow text-menta">Cartera por cobrar · Ambato</p>
                <p class="mt-3 font-display text-4xl leading-tight font-bold tracking-tight text-platino">
                    Cada factura,<br>cada abono,<br><span class="text-menta">bajo control.</span>
                </p>
                <p class="mt-4 max-w-sm text-sm text-niebla">Catálogo, facturación al contado y a crédito, abonos, alertas de mora y reportes en PDF y Excel para Privileg Distribuciones.</p>
            </div>
            <div class="grid grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-3"><p class="eyebrow text-[0.6rem] text-niebla">Ingresos</p><p class="mt-1 font-display text-sm font-semibold text-menta">Abonos</p></div>
                <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-3"><p class="eyebrow text-[0.6rem] text-niebla">Control</p><p class="mt-1 font-display text-sm font-semibold text-lila">Cuentas</p></div>
                <div class="rounded-xl border border-tinta/[0.06] bg-obsidiana/40 p-3"><p class="eyebrow text-[0.6rem] text-niebla">Alertas</p><p class="mt-1 font-display text-sm font-semibold text-cobre">Mora</p></div>
            </div>
        </section>

        {{-- Formulario --}}
        <section class="flex flex-col justify-center gap-8 p-6 sm:p-10">
            <div class="lg:hidden"><x-ui.logo /></div>
            <div>
                <h1 class="font-display text-2xl font-bold tracking-tight">Iniciar sesión</h1>
                <p class="mt-1 text-sm text-niebla">Ingrese con las credenciales asignadas por Gerencia.</p>
            </div>

            @if (session('status'))
                <div role="status" class="flex items-start gap-3 rounded-xl border border-menta/30 bg-menta/[0.08] px-4 py-3 text-sm">
                    <x-ui.icon name="check" class="mt-0.5 size-4 text-menta" />
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="flex items-start gap-3 rounded-xl border border-cobre/40 bg-cobre/[0.08] px-4 py-3 text-sm">
                    <x-ui.icon name="alerta" class="mt-0.5 size-4 text-cobre" />
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5" novalidate>
                @csrf
                <x-ui.input label="Correo electrónico" name="email" type="email" autocomplete="username" required autofocus placeholder="usuario@privileg.com" />

                <div class="flex flex-col gap-2">
                    <x-ui.password label="Contraseña" name="password" autocomplete="current-password" required />
                    <a href="{{ route('password.request') }}" class="self-end text-xs font-semibold text-menta underline-offset-4 hover:underline">¿Olvidó su contraseña?</a>
                </div>

                <x-ui.button type="submit" class="mt-1 w-full" data-enviando="Verificando…">Ingresar al sistema</x-ui.button>
            </form>

            <p class="flex items-center gap-2 text-xs text-niebla">
                <x-ui.icon name="escudo" class="size-4 text-menta" />
                Acceso restringido al personal autorizado · Contraseñas cifradas (LOPDP)
            </p>
        </section>
    </div>
</x-layouts.guest>
