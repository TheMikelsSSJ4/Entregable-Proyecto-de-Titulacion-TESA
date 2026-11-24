<?php

namespace Database\Factories;

use App\Enums\EstadoUsuario;
use App\Enums\RolNombre;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Contraseña de las cuentas generadas en pruebas automatizadas.
     */
    public const PASSWORD = 'Prueba2026!';

    protected static ?string $passwordHash = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rol_id' => fn (): int => self::rol(RolNombre::Vendedor),
            'nombre_completo' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$passwordHash ??= Hash::make(self::PASSWORD),
            'estado' => EstadoUsuario::Activo,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (): array => ['rol_id' => self::rol(RolNombre::Administrador)]);
    }

    public function gerente(): static
    {
        return $this->state(fn (): array => ['rol_id' => self::rol(RolNombre::Gerente)]);
    }

    public function contador(): static
    {
        return $this->state(fn (): array => ['rol_id' => self::rol(RolNombre::Contador)]);
    }

    public function vendedor(): static
    {
        return $this->state(fn (): array => ['rol_id' => self::rol(RolNombre::Vendedor)]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoUsuario::Inactivo]);
    }

    private static function rol(RolNombre $nombre): int
    {
        return Rol::query()->firstOrCreate(['nombre' => $nombre->value])->id;
    }
}
