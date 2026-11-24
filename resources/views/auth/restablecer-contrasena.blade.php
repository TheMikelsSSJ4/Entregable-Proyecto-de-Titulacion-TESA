<x-layouts.guest title="Nueva contraseña">
    <section class="card flex w-full max-w-md flex-col gap-7 p-6 sm:p-10">
        <x-ui.logo />
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">Crear nueva contraseña</h1>
            <p class="mt-1 text-sm text-niebla">Mínimo 8 caracteres, con mayúsculas, minúsculas y números.</p>
        </div>

        @if ($errors->has('email'))
            <div role="alert" class="flex items-start gap-3 rounded-xl border border-cobre/40 bg-cobre/[0.08] px-4 py-3 text-sm">
                <x-ui.icon name="alerta" class="mt-0.5 size-4 text-cobre" />
                <p>{{ $errors->first('email') }} <a href="{{ route('password.request') }}" class="font-semibold text-menta underline underline-offset-4">Solicitar otro enlace</a></p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-5" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.input label="Correo electrónico" name="email" type="email" :value="$email" autocomplete="username" required />
            <x-ui.password label="Nueva contraseña" name="password" autocomplete="new-password" required />
            <x-ui.password label="Confirmar contraseña" name="password_confirmation" autocomplete="new-password" required />
            <x-ui.button type="submit" icon="check" class="w-full" data-enviando="Guardando…">Guardar contraseña</x-ui.button>
        </form>
    </section>
</x-layouts.guest>
