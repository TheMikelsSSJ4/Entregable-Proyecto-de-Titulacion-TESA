<?php

namespace App\Models;

use App\Enums\EstadoCotizacion;
use Database\Factories\CotizacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Proforma comercial (tabla `cotizaciones` del DER).
 *
 * @property int $id
 * @property int $cliente_id
 * @property int $usuario_id
 * @property int $vendedor_id
 * @property Carbon $fecha_emision
 * @property string $total
 * @property EstadoCotizacion $estado
 * @property-read string $numero
 */
#[Table('cotizaciones')]
#[Fillable(['cliente_id', 'usuario_id', 'vendedor_id', 'fecha_emision', 'subtotal', 'descuento', 'iva', 'total', 'estado'])]
class Cotizacion extends Model
{
    /** @use HasFactory<CotizacionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'pendiente',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'estado' => EstadoCotizacion::class,
        ];
    }

    /**
     * @return HasMany<CotizacionDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class)->orderBy('id');
    }

    /**
     * @return HasMany<Factura, $this>
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Vendedor que realizó la cotización (puede ser distinto de quien la registró).
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'vendedor_id');
    }

    /**
     * Usuario que registró el documento en el sistema.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * El DER no define número para cotizaciones: se deriva del id (COT-000014).
     */
    public function getNumeroAttribute(): string
    {
        return 'COT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Un vendedor ve las cotizaciones que vendió o registró; Gerencia, Contabilidad y Administración ven todas.
     */
    #[Scope]
    protected function visiblesPara(Builder $query, Usuario $usuario): void
    {
        if (! $usuario->accedeAFinanzas()) {
            $query->where(fn (Builder $q) => $q->where('vendedor_id', $usuario->id)->orWhere('usuario_id', $usuario->id));
        }
    }
}
