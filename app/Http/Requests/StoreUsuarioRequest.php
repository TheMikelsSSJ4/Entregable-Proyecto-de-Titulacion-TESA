<?php

namespace App\Http\Requests;

use App\Enums\EstadoUsuario;
use App\Enums\RolNombre;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Usuario::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre_completo' => Str::squish((string) $this->input('nombre_completo')),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('usuarios', 'email')],
            'rol_id' => ['required', 'integer', self::reglaRolAsignable($this->user())],
            'estado' => ['required', Rule::enum(EstadoUsuario::class)],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    /**
     * Sólo el Administrador General puede asignar el rol de Administrador General.
     */
    public static function reglaRolAsignable(?Usuario $usuario): Exists
    {
        $regla = Rule::exists('roles', 'id');

        if (! $usuario?->esAdministrador()) {
            $regla->where(fn ($q) => $q->where('nombre', '!=', RolNombre::Administrador->value));
        }

        return $regla;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre_completo' => 'nombre completo',
            'rol_id' => 'rol',
            'password' => 'contraseña',
        ];
    }
}
