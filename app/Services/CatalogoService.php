<?php

namespace App\Services;

use App\Exceptions\OperacionNoPermitidaException;
use App\Models\Producto;

/**
 * Catálogo de productos y servicios facturables (sin inventario).
 */
class CatalogoService
{
    public function __construct(private readonly CalculadoraComercial $calculadora) {}

    /**
     * Crea o actualiza un producto. El precio final siempre lo calcula el
     * servidor; nunca se acepta el valor enviado por el navegador.
     *
     * @param  array{codigo: string, nombre: string, precio_base: string|float, porcentaje_descuento: string|float, porcentaje_iva: string|int, activo?: bool}  $datos
     */
    public function guardarProducto(array $datos, ?Producto $producto = null): Producto
    {
        $datos['precio_base'] = bcadd((string) $datos['precio_base'], '0', 2);
        $datos['porcentaje_descuento'] = bcadd((string) $datos['porcentaje_descuento'], '0', 2);
        $datos['porcentaje_iva'] = bcadd((string) $datos['porcentaje_iva'], '0', 2);
        $datos['precio_final'] = $this->calculadora->precioFinal(
            $datos['precio_base'],
            $datos['porcentaje_descuento'],
            $datos['porcentaje_iva'],
        );

        if ($producto === null) {
            return Producto::create($datos);
        }

        $producto->update($datos);

        return $producto;
    }

    /**
     * Elimina un producto que nunca se usó. Si ya consta en cotizaciones o
     * facturas se conserva (ON DELETE RESTRICT) y debe desactivarse.
     */
    public function eliminarProducto(Producto $producto): void
    {
        if ($producto->estaEnUso()) {
            throw new OperacionNoPermitidaException(
                "El producto {$producto->codigo} ya figura en cotizaciones o facturas. Desactívelo en lugar de eliminarlo."
            );
        }

        $producto->delete();
    }
}
