<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaLineasDocumento;
use App\Models\Cotizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCotizacionRequest extends FormRequest
{
    use ValidaLineasDocumento;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Cotizacion::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepararLineas();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
            'vendedor_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('estado', 'activo')],
            'fecha_emision' => ['required', 'date', 'before_or_equal:today'],
            ...$this->reglasLineas(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validarLineas($validator)];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'vendedor_id' => 'vendedor',
            'fecha_emision' => 'fecha de emisión',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vendedor_id.exists' => 'El vendedor seleccionado no existe o está inactivo.',
            ...$this->mensajesLineas(),
        ];
    }
}
