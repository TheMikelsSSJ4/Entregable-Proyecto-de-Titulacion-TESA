<?php

namespace App\Http\Requests;

use App\Rules\IdentificacionEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateEmpresaConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('configurar-empresa') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'razon_social' => Str::squish((string) $this->input('razon_social')),
            'ruc' => preg_replace('/[\s.\-]/', '', (string) $this->input('ruc')),
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
            'razon_social' => ['required', 'string', 'min:3', 'max:200'],
            'ruc' => ['required', 'string', 'size:13', new IdentificacionEcuatoriana],
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
            'razon_social' => 'razón social',
            'ruc' => 'RUC',
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
            'ruc.size' => 'El RUC de la empresa debe tener 13 dígitos.',
            'telefono.regex' => 'El teléfono sólo puede contener números, espacios, paréntesis y el signo +.',
        ];
    }
}
