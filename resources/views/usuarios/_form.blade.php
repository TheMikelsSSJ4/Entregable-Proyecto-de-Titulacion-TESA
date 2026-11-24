{{-- Campos compartidos por crear/editar usuario. Requiere $usuario y $roles. --}}
@php $editando = $usuario->exists; @endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <x-ui.input label="Nombre completo" name="nombre_completo" :value="$usuario->nombre_completo" required autocomplete="name" maxlength="150" class="md:col-span-2" />
    <x-ui.input label="Correo electrónico" name="email" type="email" :value="$usuario->email" required autocomplete="email" maxlength="150" hint="Se usa para iniciar sesión." />

    <x-ui.select label="Rol" name="rol_id" :value="$usuario->rol_id" required placeholder="Seleccione un rol"
        :options="$roles->mapWithKeys(fn ($rol) => [$rol->id => $rol->nombre->value.' — '.$rol->nombre->descripcion()])->all()" />

    <x-ui.select label="Estado" name="estado" :value="$usuario->estado?->value ?? 'activo'" required
        :options="collect(\App\Enums\EstadoUsuario::cases())->mapWithKeys(fn ($e) => [$e->value => $e->etiqueta()])->all()"
        hint="Un usuario inactivo no puede iniciar sesión." />

    <div class="hidden md:block"></div>

    <x-ui.password label="{{ $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' }}" name="password" :required="! $editando" autocomplete="new-password"
        hint="Mínimo 8 caracteres, con mayúsculas, minúsculas y números." />
    <x-ui.password label="Confirmar contraseña" name="password_confirmation" :required="! $editando" autocomplete="new-password" />
</div>
