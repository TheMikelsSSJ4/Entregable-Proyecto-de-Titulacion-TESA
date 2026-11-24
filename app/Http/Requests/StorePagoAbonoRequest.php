<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use App\Models\PagoAbono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de un pago parcial o total (RF-04). Que el monto no supere el saldo
 * se verifica en CarteraService con la factura bloqueada, no aquí, para evitar
 * condiciones de carrera entre dos abonos simultáneos.
 */
class StorePagoAbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PagoAbono::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'monto_abonado' => str_replace(',', '.', trim((string) $this->input('monto_abonado'))),
            'comprobante_referencia' => $this->filled('comprobante_referencia') ? trim((string) $this->input('comprobante_referencia')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'factura_id' => ['required', 'integer', Rule::exists('facturas', 'id')],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'monto_abonado' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'metodo_pago' => ['required', Rule::enum(MetodoPago::class)],
            'comprobante_referencia' => ['required_unless:metodo_pago,efectivo', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'factura_id' => 'factura',
            'fecha_pago' => 'fecha de pago',
            'monto_abonado' => 'monto del abono',
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
            'comprobante_referencia.required_unless' => 'Ingrese el número de comprobante para pagos que no son en efectivo.',
        ];
    }
}
