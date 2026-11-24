<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Filtros de los reportes: rango de fechas obligatorio y cliente para el estado de cuenta.
 */
class ExportarReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ver-reportes') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'cliente_id' => [
                Rule::requiredIf(fn (): bool => $this->route('tipo') === 'estado-cuenta'),
                'nullable',
                'integer',
                Rule::exists('clientes', 'id'),
            ],
            'vendedor_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'cliente_id' => 'cliente',
            'vendedor_id' => 'vendedor',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Seleccione el cliente para generar su estado de cuenta.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    /**
     * La vista previa se carga dentro de un marco: los errores se muestran ahí
     * mismo en lugar de redirigir a la pantalla de Reportes.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->routeIs('reportes.vista-previa')) {
            throw new HttpResponseException(
                response()->view('reportes.vista-previa-error', ['errores' => $validator->errors()->all()], 422)
            );
        }

        parent::failedValidation($validator);
    }

    public function desde(): Carbon
    {
        return Carbon::parse($this->validated('fecha_inicio'))->startOfDay();
    }

    public function hasta(): Carbon
    {
        return Carbon::parse($this->validated('fecha_fin'))->startOfDay();
    }

    public function cliente(): ?Cliente
    {
        $id = $this->validated('cliente_id');

        return $id ? Cliente::findOrFail((int) $id) : null;
    }

    /**
     * Vendedor elegido para filtrar el reporte de ventas (opcional).
     */
    public function vendedor(): ?Usuario
    {
        $id = $this->validated('vendedor_id');

        return $id ? Usuario::findOrFail((int) $id) : null;
    }

    public function periodoTexto(): string
    {
        $texto = 'Del '.$this->desde()->format('d/m/Y').' al '.$this->hasta()->format('d/m/Y');

        if ($this->route('tipo') === 'ventas' && ($vendedor = $this->vendedor())) {
            $texto .= ' · Vendedor: '.Str::upper($vendedor->nombre_completo);
        }

        return $texto;
    }
}
