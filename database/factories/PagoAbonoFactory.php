<?php

namespace Database\Factories;

use App\Enums\MetodoPago;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Crea el registro directamente (sin recalcular el estado de la factura).
 * Para flujos de negocio completos use CarteraService::registrarAbono().
 *
 * @extends Factory<PagoAbono>
 */
class PagoAbonoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'factura_id' => Factura::factory(),
            'usuario_id' => Usuario::factory()->contador(),
            'fecha_pago' => today(),
            'monto_abonado' => '50.00',
            'metodo_pago' => MetodoPago::Efectivo,
            'comprobante_referencia' => null,
        ];
    }
}
