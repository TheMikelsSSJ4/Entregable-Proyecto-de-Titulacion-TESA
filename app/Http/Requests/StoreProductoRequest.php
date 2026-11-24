<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * El precio final NO se recibe del formulario: CatalogoService lo calcula.
 */
class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Producto::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => Str::upper(Str::squish((string) $this->input('codigo'))),
            'nombre' => Str::squish((string) $this->input('nombre')),
            'precio_base' => str_replace(',', '.', trim((string) $this->input('precio_base'))),
            'porcentaje_descuento' => str_replace(',', '.', trim((string) ($this->input('porcentaje_descuento') ?: '0'))),
            'activo' => $this->boolean('activo'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9\-]*$/', $this->reglaUnica()],
            'nombre' => ['required', 'string', 'min:3', 'max:200'],
            'precio_base' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'porcentaje_descuento' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'porcentaje_iva' => ['required', 'integer', Rule::in(config('contable.tarifas_iva'))],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'precio_base' => 'precio base',
            'porcentaje_descuento' => '% de descuento',
            'porcentaje_iva' => '% de IVA',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código sólo admite letras, números y guiones (por ejemplo ABR-001).',
            'codigo.unique' => 'Ya existe un producto con este código.',
        ];
    }

    protected function reglaUnica(): Unique
    {
        return Rule::unique('productos', 'codigo');
    }
}
