<?php

namespace App\Http\Requests;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $objetivo = $this->route('usuario');

        return $objetivo instanceof Usuario && ($this->user()?->can('update', $objetivo) ?? false);
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
        /** @var Usuario $objetivo */
        $objetivo = $this->route('usuario');

        return [
            'nombre_completo' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('usuarios', 'email')->ignore($objetivo->id)],
            'rol_id' => ['required', 'integer', StoreUsuarioRequest::reglaRolAsignable($this->user())],
            'estado' => ['required', Rule::enum(EstadoUsuario::class)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    /**
     * Evita que un gerente se bloquee a sí mismo (desactivándose o quitándose el rol).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Usuario $objetivo */
                $objetivo = $this->route('usuario');

                if ($objetivo->id !== $this->user()->id) {
                    return;
                }

                if ((int) $this->input('rol_id') !== $objetivo->rol_id) {
                    $validator->errors()->add('rol_id', 'No puede cambiar su propio rol.');
                }

                if ($this->input('estado') !== EstadoUsuario::Activo->value) {
                    $validator->errors()->add('estado', 'No puede desactivar su propia cuenta.');
                }
            },
        ];
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
