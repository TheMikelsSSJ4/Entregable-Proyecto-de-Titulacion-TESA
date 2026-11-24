<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use App\Enums\TipoTransaccion;
use App\Http\Requests\Concerns\ValidaLineasDocumento;
use App\Models\Cotizacion;
use App\Models\Factura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Registro de una venta maestro-detalle (RF-02). Subtotal, descuento, IVA y total
 * NO se reciben del navegador: FacturacionService los calcula desde el catálogo.
 */
class StoreFacturaRequest extends FormRequest
{
    use ValidaLineasDocumento;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Factura::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['numero_factura' => trim((string) $this->input('numero_factura'))]);
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
            'numero_factura' => ['required', 'string', 'regex:/^\d{3}-\d{3}-\d{9}$/', Rule::unique('facturas', 'numero_factura')],
            'tipo_transaccion' => ['required', Rule::enum(TipoTransaccion::class)],
            'fecha_emision' => ['required', 'date', 'before_or_equal:today'],
            'fecha_vencimiento' => ['exclude_unless:tipo_transaccion,credito', 'required', 'date', 'after:fecha_emision'],
            'metodo_pago' => ['exclude_unless:tipo_transaccion,contado', 'required', Rule::enum(MetodoPago::class)],
            'comprobante_referencia' => ['exclude_unless:tipo_transaccion,contado', 'nullable', 'string', 'max:50'],
            'cotizacion_id' => ['nullable', 'integer', Rule::exists('cotizaciones', 'id')],
            ...$this->reglasLineas(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validarLineas($validator);

                if ($validator->errors()->has('cotizacion_id') || ! $this->filled('cotizacion_id')) {
                    return;
                }

                $cotizacion = Cotizacion::find($this->integer('cotizacion_id'));

                if ($cotizacion && ! $this->user()->can('view', $cotizacion)) {
                    $validator->errors()->add('cotizacion_id', 'No tiene acceso a la cotización seleccionada.');
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
            'cliente_id' => 'cliente',
            'vendedor_id' => 'vendedor',
            'numero_factura' => 'número de factura',
            'tipo_transaccion' => 'tipo de transacción',
            'fecha_emision' => 'fecha de emisión',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'metodo_pago' => 'método de pago',
            'comprobante_referencia' => 'comprobante / referencia',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vendedor_id.exists' => 'El vendedor seleccionado no existe o está inactivo.',
            'numero_factura.regex' => 'El número de factura debe tener el formato SRI 001-001-000000123.',
            'numero_factura.unique' => 'Este número de factura ya está registrado.',
            'fecha_vencimiento.after' => 'Una venta a crédito debe vencer después de la fecha de emisión.',
            ...$this->mensajesLineas(),
        ];
    }
}
