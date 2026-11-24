<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\RolNombre;
use App\Notifications\RestablecerContrasenaNotification;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Usuario interno del sistema (tabla `usuarios` del DER).
 *
 * @property int $id
 * @property int $rol_id
 * @property string $nombre_completo
 * @property string $email
 * @property string $password
 * @property EstadoUsuario $estado
 * @property-read Rol $rol
 */
#[Table('usuarios')]
#[Fillable(['rol_id', 'nombre_completo', 'email', 'password', 'estado'])]
#[Hidden(['password'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    /**
     * La tabla del DER no tiene `remember_token`, por lo que la opción
     * "recordarme" queda deshabilitada (sesión expira según SESSION_LIFETIME).
     *
     * @var string
     */
    protected $rememberTokenName = '';

    /**
     * El rol se consulta en cada autorización: se carga siempre junto al usuario.
     *
     * @var list<string>
     */
    protected $with = ['rol'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'estado' => EstadoUsuario::class,
        ];
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

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
     * @return HasMany<PagoAbono, $this>
     */
    public function pagosAbonos(): HasMany
    {
        return $this->hasMany(PagoAbono::class);
    }

    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('estado', EstadoUsuario::Activo->value);
    }

    /**
     * Usuarios activos que pueden figurar como vendedor de una venta o cotización.
     *
     * @return array<int, string> [id => "Nombre — Rol"]
     */
    public static function opcionesVendedor(): array
    {
        return static::query()
            ->activos()
            ->orderBy('nombre_completo')
            ->get()
            ->mapWithKeys(fn (Usuario $usuario): array => [
                $usuario->id => $usuario->nombre_completo.' — '.$usuario->rol->nombre->value,
            ])
            ->all();
    }

    public function tieneRol(RolNombre ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function esAdministrador(): bool
    {
        return $this->tieneRol(RolNombre::Administrador);
    }

    public function esGerente(): bool
    {
        return $this->tieneRol(RolNombre::Gerente);
    }

    public function esContador(): bool
    {
        return $this->tieneRol(RolNombre::Contador);
    }

    public function esVendedor(): bool
    {
        return $this->tieneRol(RolNombre::Vendedor);
    }

    /**
     * Administrador, Gerente y Contador ven la información financiera completa.
     */
    public function accedeAFinanzas(): bool
    {
        return $this->tieneRol(RolNombre::Administrador, RolNombre::Gerente, RolNombre::Contador);
    }

    /**
     * Envía el enlace de "Olvidé mi contraseña" con un correo en español.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new RestablecerContrasenaNotification($token));
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoUsuario::Activo;
    }

    public function iniciales(): string
    {
        return Str::of($this->nombre_completo)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $parte): string => Str::upper(Str::substr($parte, 0, 1)))
            ->implode('');
    }
}
