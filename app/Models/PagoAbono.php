<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Database\Factories\PagoAbonoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pago total o abono parcial aplicado a una factura (tabla `pagos_abonos` del DER).
 *
 * @property int $id
 * @property int $factura_id
 * @property int $usuario_id
 * @property Carbon $fecha_pago
 * @property string $monto_abonado
 * @property MetodoPago $metodo_pago
 * @property string|null $comprobante_referencia
 */
#[Table('pagos_abonos')]
#[Fillable(['factura_id', 'usuario_id', 'fecha_pago', 'monto_abonado', 'metodo_pago', 'comprobante_referencia'])]
class PagoAbono extends Model
{
    /** @use HasFactory<PagoAbonoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'monto_abonado' => 'decimal:2',
            'metodo_pago' => MetodoPago::class,
        ];
    }

    /**
     * @return BelongsTo<Factura, $this>
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
