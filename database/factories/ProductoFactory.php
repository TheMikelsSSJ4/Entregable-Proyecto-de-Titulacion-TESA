<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Services\CalculadoraComercial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Producto de $10.00 con IVA 15% y sin descuento (precio final $11.50) por defecto.
 *
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'PRD-'.fake()->unique()->numerify('#####'),
            'nombre' => 'Producto '.fake()->unique()->words(2, true),
            'precio_base' => '10.00',
            'porcentaje_descuento' => '0.00',
            'porcentaje_iva' => '15.00',
            'precio_final' => '11.50',
            'activo' => true,
        ];
    }

    /**
     * Precio, descuento e IVA a la medida; el precio final se calcula como en el sistema.
     */
    public function precio(string $precioBase, string $porcentajeDescuento = '0', string $porcentajeIva = '15'): static
    {
        return $this->state(fn (): array => [
            'precio_base' => $precioBase,
            'porcentaje_descuento' => $porcentajeDescuento,
            'porcentaje_iva' => $porcentajeIva,
            'precio_final' => (new CalculadoraComercial)->precioFinal($precioBase, $porcentajeDescuento, $porcentajeIva),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['activo' => false]);
    }
}
