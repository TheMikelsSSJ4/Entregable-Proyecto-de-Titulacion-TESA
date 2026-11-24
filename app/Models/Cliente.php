<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ruc_cedula
 * @property string $razon_social
 * @property string $direccion
 * @property string|null $telefono
 * @property string|null $correo
 */
#[Table('clientes')]
#[Fillable(['ruc_cedula', 'razon_social', 'direccion', 'telefono', 'correo'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    /**
     * @return HasMany<Factura, $this>
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * @return HasMany<Cotizacion, $this>
     */
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    /**
     * Búsqueda por RUC/cédula o razón social (ILIKE en PostgreSQL).
     */
    #[Scope]
    protected function buscar(Builder $query, ?string $termino): void
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $q) use ($termino): void {
            $q->whereLike('razon_social', "%{$termino}%")
                ->orWhereLike('ruc_cedula', "{$termino}%");
        });
    }

    public function tipoIdentificacion(): string
    {
        return strlen($this->ruc_cedula) === 13 ? 'RUC' : 'Cédula';
    }
}
