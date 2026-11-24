<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use App\Rules\IdentificacionEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cliente::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ruc_cedula' => preg_replace('/[\s.\-]/', '', (string) $this->input('ruc_cedula')),
            'razon_social' => Str::squish((string) $this->input('razon_social')),
            'direccion' => Str::squish((string) $this->input('direccion')),
            'telefono' => $this->filled('telefono') ? trim((string) $this->input('telefono')) : null,
            'correo' => $this->filled('correo') ? Str::lower(trim((string) $this->input('correo'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ruc_cedula' => ['required', 'string', new IdentificacionEcuatoriana, $this->reglaUnica()],
            'razon_social' => ['required', 'string', 'min:3', 'max:200'],
            'direccion' => ['required', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+()\s]{7,20}$/'],
            'correo' => ['nullable', 'string', 'email:rfc', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ruc_cedula' => 'identificación (RUC/cédula)',
            'razon_social' => 'razón social',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'correo' => 'correo electrónico',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ruc_cedula.unique' => 'Ya existe un cliente registrado con esta identificación.',
            'telefono.regex' => 'El teléfono sólo puede contener números, espacios, paréntesis y el signo +.',
        ];
    }

    protected function reglaUnica(): Unique
    {
        return Rule::unique('clientes', 'ruc_cedula');
    }
}
