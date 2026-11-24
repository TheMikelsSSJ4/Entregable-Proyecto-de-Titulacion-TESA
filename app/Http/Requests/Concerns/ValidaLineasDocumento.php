<?php

namespace App\Http\Requests\Concerns;

use App\Models\Producto;
use App\Services\CalculadoraComercial;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación compartida de las líneas maestro-detalle (facturas y cotizaciones).
 *
 * Del navegador se aceptan: producto, cantidad, precio unitario (manual, sin IVA),
 * % de descuento (manual, 0-100), % de IVA (sólo tarifas vigentes) y la marca
 * "guardar también en el catálogo". Los montos siempre los calcula el servidor.
 */
trait ValidaLineasDocumento
{
    /**
     * Total máximo que admite una columna DECIMAL(12,2).
     */
    private string $totalMaximoDocumento = '9999999999.99';

    /**
     * Quita líneas vacías y normaliza la coma decimal.
     */
    protected function prepararLineas(): void
    {
        $decimal = fn ($valor): ?string => filled($valor) ? str_replace(',', '.', trim((string) $valor)) : null;

        $lineas = collect((array) $this->input('lineas', []))
            ->filter(fn ($linea): bool => is_array($linea) && (filled($linea['producto_id'] ?? null) || filled($linea['cantidad'] ?? null)))
            ->map(fn (array $linea): array => [
                'producto_id' => $linea['producto_id'] ?? null,
                'cantidad' => (string) $decimal($linea['cantidad'] ?? null),
                'precio_unitario' => $decimal($linea['precio_unitario'] ?? null),
                'porcentaje_descuento' => $decimal($linea['porcentaje_descuento'] ?? null),
                'porcentaje_iva' => $decimal($linea['porcentaje_iva'] ?? null),
                'actualizar_catalogo' => filter_var($linea['actualizar_catalogo'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values()
            ->all();

        $this->merge(['lineas' => $lineas]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglasLineas(): array
    {
        return [
            'lineas' => ['required', 'array', 'min:1', 'max:50'],
            'lineas.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('activo', true)],
            'lineas.*.cantidad' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999'],
            'lineas.*.precio_unitario' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'lineas.*.porcentaje_descuento' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'lineas.*.porcentaje_iva' => ['nullable', 'integer', Rule::in(config('contable.tarifas_iva'))],
            'lineas.*.actualizar_catalogo' => ['boolean'],
        ];
    }

    /**
     * Controles que dependen de varias líneas o del usuario:
     * - sólo quien puede editar productos puede guardar cambios en el catálogo;
     * - el total del documento debe caber en DECIMAL(12,2).
     */
    protected function validarLineas(Validator $validator): void
    {
        $hayErroresEnLineas = collect($validator->errors()->keys())
            ->contains(fn (string $clave): bool => str_starts_with($clave, 'lineas'));

        if ($hayErroresEnLineas) {
            return;
        }

        $lineas = $this->input('lineas');
        $productos = Producto::query()->whereIn('id', array_column($lineas, 'producto_id'))->get()->keyBy('id');
        $calculadora = app(CalculadoraComercial::class);
        $total = '0.00';

        foreach ($lineas as $indice => $linea) {
            $producto = $productos->get((int) $linea['producto_id']);

            if ($producto === null) {
                continue;
            }

            if ($linea['actualizar_catalogo'] && ! $this->user()->can('update', $producto)) {
                $validator->errors()->add("lineas.{$indice}.actualizar_catalogo", 'No tiene permiso para modificar el catálogo de productos.');
            }

            $total = bcadd($total, $calculadora->lineaDesdeProducto(
                $producto,
                (string) $linea['cantidad'],
                $linea['porcentaje_descuento'],
                $linea['porcentaje_iva'],
                $linea['precio_unitario'],
            )['total'], 2);
        }

        if (bccomp($total, $this->totalMaximoDocumento, 2) > 0) {
            $validator->errors()->add('lineas', 'El total del documento supera el máximo permitido ($9,999,999,999.99).');
        }
    }

    /**
     * @return array<string, string>
     */
    protected function mensajesLineas(): array
    {
        return [
            'lineas.required' => 'Agregue al menos un producto al documento.',
            'lineas.min' => 'Agregue al menos un producto al documento.',
            'lineas.max' => 'Un documento admite máximo 50 líneas.',
            'lineas.*.producto_id.required' => 'Seleccione el producto de la línea.',
            'lineas.*.producto_id.distinct' => 'El producto está repetido: ajuste la cantidad en una sola línea.',
            'lineas.*.producto_id.exists' => 'El producto no existe o está inactivo.',
            'lineas.*.cantidad.required' => 'Ingrese la cantidad.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'lineas.*.cantidad.max' => 'La cantidad máxima por línea es 99,999.',
            'lineas.*.cantidad.decimal' => 'La cantidad admite máximo 2 decimales.',
            'lineas.*.precio_unitario.min' => 'El precio unitario debe ser mayor que cero.',
            'lineas.*.precio_unitario.max' => 'El precio unitario máximo es $99,999,999.99.',
            'lineas.*.precio_unitario.decimal' => 'El precio unitario admite máximo 2 decimales.',
            'lineas.*.precio_unitario.numeric' => 'El precio unitario debe ser un número.',
            'lineas.*.porcentaje_descuento.min' => 'El descuento no puede ser negativo.',
            'lineas.*.porcentaje_descuento.max' => 'El descuento no puede superar el 100%.',
            'lineas.*.porcentaje_descuento.decimal' => 'El descuento admite máximo 2 decimales.',
            'lineas.*.porcentaje_descuento.numeric' => 'El descuento debe ser un número.',
            'lineas.*.porcentaje_iva.in' => 'Seleccione una tarifa de IVA vigente.',
            'lineas.*.porcentaje_iva.integer' => 'Seleccione una tarifa de IVA vigente.',
        ];
    }
}
