<?php

namespace App\Models;

use App\Enums\EstadoFactura;
use App\Enums\TipoTransaccion;
use Carbon\CarbonInterface;
use Database\Factories\FacturaFactory;
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
 * Factura de venta al contado o a crédito (tabla `facturas` del DER).
 *
 * El saldo y los días de mora NO se almacenan: se calculan a partir de
 * pagos_abonos y fecha_vencimiento para que nunca queden desactualizados.
 *
 * @property int $id
 * @property string $numero_factura
 * @property int $cliente_id
 * @property int $usuario_id
 * @property int $vendedor_id
 * @property TipoTransaccion $tipo_transaccion
 * @property Carbon $fecha_emision
 * @property Carbon $fecha_vencimiento
 * @property string $subtotal
 * @property string $iva
 * @property string $total
 * @property EstadoFactura $estado
 * @property string|null $total_abonado Agregado vía withSum('pagos as total_abonado', ...)
 */
#[Table('facturas')]
#[Fillable([
    'numero_factura', 'cliente_id', 'usuario_id', 'vendedor_id', 'cotizacion_id', 'tipo_transaccion', 'fecha_emision',
    'fecha_vencimiento', 'subtotal', 'descuento', 'iva', 'total', 'estado',
])]
class Factura extends Model
{
    /** @use HasFactory<FacturaFactory> */
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
            'tipo_transaccion' => TipoTransaccion::class,
            'estado' => EstadoFactura::class,
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<FacturaDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Cotizacion, $this>
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Vendedor que realizó la venta (puede ser distinto de quien la registró).
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
     * @return HasMany<PagoAbono, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoAbono::class);
    }

    #[Scope]
    protected function abiertas(Builder $query): void
    {
        $query->whereIn('estado', EstadoFactura::abiertos());
    }

    #[Scope]
    protected function vencidas(Builder $query): void
    {
        $query->whereIn('estado', EstadoFactura::abiertos())
            ->whereDate('fecha_vencimiento', '<', today());
    }

    #[Scope]
    protected function porVencer(Builder $query): void
    {
        $query->whereIn('estado', EstadoFactura::abiertos())
            ->whereBetween('fecha_vencimiento', [
                today()->toDateString(),
                today()->addDays((int) config('contable.dias_alerta_previa'))->toDateString(),
            ]);
    }

    /**
     * Filtra por fecha de emisión; cualquiera de los extremos puede omitirse.
     */
    #[Scope]
    protected function emitidasEntre(Builder $query, ?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): void
    {
        $query->when($desde, fn (Builder $q) => $q->whereDate('fecha_emision', '>=', $desde->toDateString()))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha_emision', '<=', $hasta->toDateString()));
    }

    /**
     * Un vendedor ve las facturas que vendió o registró; Gerencia, Contabilidad y Administración ven todas.
     */
    #[Scope]
    protected function visiblesPara(Builder $query, Usuario $usuario): void
    {
        if (! $usuario->accedeAFinanzas()) {
            $query->where(fn (Builder $q) => $q->where('vendedor_id', $usuario->id)->orWhere('usuario_id', $usuario->id));
        }
    }

    #[Scope]
    protected function buscar(Builder $query, ?string $termino): void
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $q) use ($termino): void {
            $q->whereLike('numero_factura', "%{$termino}%")
                ->orWhereHas('cliente', fn (Builder $c) => $c->buscar($termino));
        });
    }

    /**
     * Suma de abonos. Usa el agregado `total_abonado` si la consulta lo cargó
     * con withSum() para evitar consultas N+1 en listados.
     */
    public function totalAbonado(): string
    {
        if (array_key_exists('total_abonado', $this->attributes)) {
            return bcadd((string) ($this->attributes['total_abonado'] ?? '0'), '0', 2);
        }

        if ($this->relationLoaded('pagos')) {
            return $this->pagos->reduce(
                fn (string $acumulado, PagoAbono $pago): string => bcadd($acumulado, (string) $pago->monto_abonado, 2),
                '0.00',
            );
        }

        return bcadd((string) ($this->pagos()->sum('monto_abonado') ?? '0'), '0', 2);
    }

    public function saldo(): string
    {
        if ($this->estado === EstadoFactura::Anulada) {
            return '0.00';
        }

        return bcsub((string) $this->total, $this->totalAbonado(), 2);
    }

    public function estaAbierta(): bool
    {
        return in_array($this->estado->value, EstadoFactura::abiertos(), true);
    }

    public function esCredito(): bool
    {
        return $this->tipo_transaccion === TipoTransaccion::Credito;
    }

    /**
     * Días transcurridos desde el vencimiento (0 si no está vencida o ya fue pagada).
     */
    public function diasMora(): int
    {
        if (! $this->estaAbierta()) {
            return 0;
        }

        return max(0, (int) $this->fecha_vencimiento->diffInDays(today(), false));
    }

    /**
     * Días que faltan para el vencimiento (negativo si ya venció).
     */
    public function diasParaVencer(): int
    {
        return (int) today()->diffInDays($this->fecha_vencimiento, false);
    }

    public function estaVencida(): bool
    {
        return $this->diasMora() > 0;
    }

    public function estaPorVencer(): bool
    {
        if (! $this->estaAbierta() || $this->estaVencida()) {
            return false;
        }

        return $this->diasParaVencer() <= (int) config('contable.dias_alerta_previa');
    }

    /**
     * Etiqueta y tono para el indicador visual de cartera (RF-05).
     *
     * @return array{etiqueta: string, tono: string}
     */
    public function indicadorCartera(): array
    {
        return match (true) {
            $this->estaVencida() => ['etiqueta' => 'Vencida', 'tono' => 'cobre'],
            $this->estaPorVencer() => ['etiqueta' => 'Por vencer', 'tono' => 'oro'],
            default => ['etiqueta' => $this->estado->etiqueta(), 'tono' => $this->estado->tono()],
        };
    }
}
