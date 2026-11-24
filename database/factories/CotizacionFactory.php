<?php

namespace Database\Factories;

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cabecera de cotización (500.00 + IVA 75.00 = 575.00) sin líneas de detalle.
 * Para cotizaciones con líneas use FacturacionService::registrarCotizacion().
 *
 * @extends Factory<Cotizacion>
 */
class CotizacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            // Por defecto el vendedor es quien registra el documento.
            'vendedor_id' => fn (array $atributos) => $atributos['usuario_id'],
            'fecha_emision' => today()->subDays(3),
            'subtotal' => '500.00',
            'descuento' => '0.00',
            'iva' => '75.00',
            'total' => '575.00',
            'estado' => EstadoCotizacion::Pendiente,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoCotizacion::Aprobada]);
    }

    public function facturada(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoCotizacion::Facturada]);
    }
}
