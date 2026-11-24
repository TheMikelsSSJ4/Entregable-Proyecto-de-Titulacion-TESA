<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto o servicio del catálogo de facturación (sin inventario).
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $precio_base
 * @property string $porcentaje_descuento
 * @property string $porcentaje_iva
 * @property string $precio_final
 * @property bool $activo
 */
#[Table('productos')]
#[Fillable(['codigo', 'nombre', 'precio_base', 'porcentaje_descuento', 'porcentaje_iva', 'precio_final', 'activo'])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'activo' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'porcentaje_descuento' => 'decimal:2',
            'porcentaje_iva' => 'decimal:2',
            'precio_final' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FacturaDetalle, $this>
     */
    public function facturaDetalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    /**
     * @return HasMany<CotizacionDetalle, $this>
     */
    public function cotizacionDetalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    #[Scope]
    protected function buscar(Builder $query, ?string $termino): void
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $q) use ($termino): void {
            $q->whereLike('nombre', "%{$termino}%")
                ->orWhereLike('codigo', "%{$termino}%");
        });
    }

    /**
     * Productos activos para los formularios de cotización y factura.
     *
     * @return list<array{id: int, codigo: string, nombre: string, precio_base: string, porcentaje_descuento: string, porcentaje_iva: string, precio_final: string}>
     */
    public static function catalogoParaFormularios(): array
    {
        return static::query()
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto): array => $producto->paraFormulario())
            ->values()
            ->all();
    }

    public function estaEnUso(): bool
    {
        return $this->facturaDetalles()->exists() || $this->cotizacionDetalles()->exists();
    }

    /**
     * Datos mínimos para el formulario maestro-detalle (Alpine.js).
     *
     * @return array{id: int, codigo: string, nombre: string, precio_base: string, porcentaje_descuento: string, porcentaje_iva: string, precio_final: string}
     */
    public function paraFormulario(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'precio_base' => (string) $this->precio_base,
            'porcentaje_descuento' => (string) $this->porcentaje_descuento,
            'porcentaje_iva' => (string) $this->porcentaje_iva,
            'precio_final' => (string) $this->precio_final,
        ];
    }
}
