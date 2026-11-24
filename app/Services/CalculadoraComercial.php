<?php

namespace App\Services;

use App\Models\Producto;

/**
 * Cálculos comerciales con BCMath (sin errores de punto flotante).
 *
 * Orden de cálculo de cada línea, igual en productos, cotizaciones y facturas:
 *   bruto     = cantidad × precio unitario
 *   descuento = bruto × % descuento          (redondeo a 2 decimales)
 *   subtotal  = bruto − descuento            (base imponible)
 *   IVA       = subtotal × % IVA             (redondeo a 2 decimales)
 *   total     = subtotal + IVA
 */
class CalculadoraComercial
{
    /**
     * @return array{bruto: string, descuento: string, subtotal: string, iva: string, total: string}
     */
    public function montos(string $cantidad, string $precioUnitario, string $porcentajeDescuento, string $porcentajeIva): array
    {
        $bruto = $this->redondear(bcmul($cantidad, $precioUnitario, 6));
        $descuento = $this->redondear(bcdiv(bcmul($bruto, $porcentajeDescuento, 6), '100', 6));
        $subtotal = bcsub($bruto, $descuento, 2);
        $iva = $this->redondear(bcdiv(bcmul($subtotal, $porcentajeIva, 6), '100', 6));

        return [
            'bruto' => $bruto,
            'descuento' => $descuento,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => bcadd($subtotal, $iva, 2),
        ];
    }

    /**
     * Precio de venta unitario con descuento e IVA incluidos.
     */
    public function precioFinal(string $precioBase, string $porcentajeDescuento, string $porcentajeIva): string
    {
        return $this->montos('1', $precioBase, $porcentajeDescuento, $porcentajeIva)['total'];
    }

    /**
     * Línea de detalle lista para guardar. Precio unitario, % de descuento y % de IVA
     * pueden ajustarse a mano en el documento; si llegan en null se usan los del catálogo.
     * Los valores aplicados quedan congelados en la línea.
     *
     * @return array{producto_id: int, descripcion: string, cantidad: string, precio_unitario: string, porcentaje_descuento: string, descuento: string, porcentaje_iva: string, subtotal: string, iva: string, total: string}
     */
    public function lineaDesdeProducto(
        Producto $producto,
        string $cantidad,
        ?string $porcentajeDescuento = null,
        ?string $porcentajeIva = null,
        ?string $precioUnitario = null,
    ): array {
        $cantidad = bcadd($cantidad, '0', 2);
        $precioUnitario = bcadd($precioUnitario ?? (string) $producto->precio_base, '0', 2);
        $porcentajeDescuento = bcadd($porcentajeDescuento ?? (string) $producto->porcentaje_descuento, '0', 2);
        $porcentajeIva = bcadd($porcentajeIva ?? (string) $producto->porcentaje_iva, '0', 2);

        $montos = $this->montos($cantidad, $precioUnitario, $porcentajeDescuento, $porcentajeIva);

        return [
            'producto_id' => $producto->id,
            'descripcion' => $producto->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'porcentaje_descuento' => $porcentajeDescuento,
            'descuento' => $montos['descuento'],
            'porcentaje_iva' => $porcentajeIva,
            'subtotal' => $montos['subtotal'],
            'iva' => $montos['iva'],
            'total' => $montos['total'],
        ];
    }

    /**
     * Totales de cabecera a partir de las líneas.
     *
     * @param  iterable<array{descuento: string, subtotal: string, iva: string, total: string}>  $lineas
     * @return array{subtotal: string, descuento: string, iva: string, total: string}
     */
    public function totalizar(iterable $lineas): array
    {
        $totales = ['subtotal' => '0.00', 'descuento' => '0.00', 'iva' => '0.00', 'total' => '0.00'];

        foreach ($lineas as $linea) {
            foreach (array_keys($totales) as $campo) {
                $totales[$campo] = bcadd($totales[$campo], (string) $linea[$campo], 2);
            }
        }

        return $totales;
    }

    private function redondear(string $valor): string
    {
        return bcadd(bcround($valor, 2), '0', 2);
    }
}
