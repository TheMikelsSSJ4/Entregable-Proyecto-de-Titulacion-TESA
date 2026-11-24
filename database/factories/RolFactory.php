<?php

namespace Database\Factories;

use App\Enums\RolNombre;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => RolNombre::Vendedor,
        ];
    }
}
