<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Datos de la empresa emisora (registro único, id = 1). Se inyectan en las
 * cabeceras de todos los PDFs y Excels del sistema.
 *
 * @property int $id
 * @property string $razon_social
 * @property string $ruc
 * @property string $direccion
 * @property string|null $telefono
 * @property string|null $correo
 */
#[Table('empresa_configuracion')]
#[Fillable(['razon_social', 'ruc', 'direccion', 'telefono', 'correo'])]
class EmpresaConfiguracion extends Model
{
    public const ID_UNICO = 1;

    /**
     * Configuración vigente. Si todavía no se registró, devuelve una instancia
     * (sin guardar) con los valores por defecto de config/contable.php.
     */
    public static function actual(): self
    {
        return static::query()->find(self::ID_UNICO)
            ?? new static(config('contable.empresa_defecto'));
    }

    /**
     * Línea de contacto para cabeceras: "RUC … · Dirección · Tel. … · correo".
     */
    public function lineaContacto(): string
    {
        return collect([
            'RUC '.$this->ruc,
            $this->direccion,
            $this->telefono ? 'Tel. '.$this->telefono : null,
            $this->correo,
        ])->filter()->implode(' · ');
    }
}
