<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ruc_cedula' => self::cedulaValida().'001',
            'razon_social' => fake()->company(),
            'direccion' => fake()->streetAddress().', Ambato',
            'telefono' => '03'.fake()->numerify('#######'),
            'correo' => fake()->unique()->safeEmail(),
        ];
    }

    /**
     * Genera una cédula ecuatoriana con dígito verificador módulo 10 correcto.
     */
    public static function cedulaValida(): string
    {
        do {
            $base = sprintf('%02d', fake()->numberBetween(1, 24))
                .fake()->numberBetween(0, 5)
                .fake()->numerify('######');

            $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
            $suma = 0;

            foreach ($coeficientes as $i => $coeficiente) {
                $producto = (int) $base[$i] * $coeficiente;
                $suma += $producto >= 10 ? $producto - 9 : $producto;
            }

            $cedula = $base.((10 - ($suma % 10)) % 10);
        } while (Cliente::query()->where('ruc_cedula', 'like', $cedula.'%')->exists());

        return $cedula;
    }
}
