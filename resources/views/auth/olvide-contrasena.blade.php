<x-layouts.guest title="Recuperar contraseña">
    <section class="card flex w-full max-w-md flex-col gap-7 p-6 sm:p-10">
        <x-ui.logo />
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">¿Olvidó su contraseña?</h1>
            <p class="mt-1 text-sm text-niebla">Ingrese el correo de su cuenta y le enviaremos un enlace para crear una nueva contraseña.</p>
        </div>

        @if (session('status'))
            <div role="status" class="flex items-start gap-3 rounded-xl border border-menta/30 bg-menta/[0.08] px-4 py-3 text-sm">
                <x-ui.icon name="correo" class="mt-0.5 size-4 text-menta" />
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5" novalidate>
            @csrf
            <x-ui.input label="Correo electrónico" name="email" type="email" autocomplete="email" required autofocus placeholder="usuario@privileg.com" />
            <x-ui.button type="submit" icon="correo" class="w-full" data-enviando="Enviando…">Enviar enlace de recuperación</x-ui.button>
        </form>

        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 self-center text-sm text-niebla hover:text-platino">
            <x-ui.icon name="atras" class="size-4" />Volver a iniciar sesión
        </a>
    </section>
</x-layouts.guest>
