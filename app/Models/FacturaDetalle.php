<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de una factura (producto, cantidad y montos congelados al vender).
 *
 * @property int $id
 * @property int $factura_id
 * @property int $producto_id
 * @property string $descripcion
 * @property string $cantidad
 * @property string $precio_unitario
 * @property string $porcentaje_descuento
 * @property string $descuento
 * @property string $porcentaje_iva
 * @property string $subtotal
 * @property string $iva
 * @property string $total
 */
#[Table('factura_detalles', timestamps: false)]
#[Fillable([
    'factura_id', 'producto_id', 'descripcion', 'cantidad', 'precio_unitario', 'porcentaje_descuento',
    'descuento', 'porcentaje_iva', 'subtotal', 'iva', 'total',
])]
class FacturaDetalle extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'descuento' => 'decimal:2',
            'porcentaje_iva' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
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
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
