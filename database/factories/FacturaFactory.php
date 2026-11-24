<?php

namespace Database\Factories;

use App\Enums\EstadoFactura;
use App\Enums\TipoTransaccion;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factura a crédito pendiente (total 115.00 = 100.00 + IVA 15%) por defecto.
 *
 * @extends Factory<Factura>
 */
class FacturaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_factura' => '001-001-'.str_pad((string) fake()->unique()->numberBetween(1, 999_999_999), 9, '0', STR_PAD_LEFT),
            'cliente_id' => Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            // Por defecto el vendedor es quien registra el documento.
            'vendedor_id' => fn (array $atributos) => $atributos['usuario_id'],
            'tipo_transaccion' => TipoTransaccion::Credito,
            'fecha_emision' => today()->subDays(10),
            'fecha_vencimiento' => today()->addDays(20),
            'subtotal' => '100.00',
            'iva' => '15.00',
            'total' => '115.00',
            'estado' => EstadoFactura::Pendiente,
        ];
    }

    /**
     * Factura a crédito cuyo vencimiento fue hace $dias días.
     */
    public function vencida(int $dias = 15): static
    {
        return $this->state(fn (): array => [
            'fecha_emision' => today()->subDays($dias + 30),
            'fecha_vencimiento' => today()->subDays($dias),
        ]);
    }

    /**
     * @param  numeric-string  $subtotal
     */
    public function conMontos(string $subtotal, string $iva): static
    {
        return $this->state(fn (): array => [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => bcadd($subtotal, $iva, 2),
        ]);
    }
}
