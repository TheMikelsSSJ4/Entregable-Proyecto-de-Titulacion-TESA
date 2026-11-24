<?php

namespace Tests\Unit;

use App\Services\CalculadoraComercial;
use PHPUnit\Framework\TestCase;

class CalculadoraComercialTest extends TestCase
{
    public function test_calcula_descuento_base_imponible_iva_y_total_de_una_linea(): void
    {
        $montos = (new CalculadoraComercial)->montos('3', '21.30', '10', '15');

        $this->assertSame('63.90', $montos['bruto']);
        $this->assertSame('6.39', $montos['descuento']);
        $this->assertSame('57.51', $montos['subtotal']);
        $this->assertSame('8.63', $montos['iva']);
        $this->assertSame('66.14', $montos['total']);
    }

    public function test_redondea_medio_centavo_hacia_arriba(): void
    {
        $montos = (new CalculadoraComercial)->montos('1', '9.50', '0', '15');

        $this->assertSame('1.43', $montos['iva']);
        $this->assertSame('10.93', $montos['total']);
    }

    public function test_precio_final_de_producto_con_descuento_e_iva(): void
    {
        $calculadora = new CalculadoraComercial;

        $this->assertSame('10.93', $calculadora->precioFinal('10.00', '5', '15'));
        $this->assertSame('23.50', $calculadora->precioFinal('23.50', '0', '0'));
    }

    public function test_totaliza_lineas_sin_errores_de_punto_flotante(): void
    {
        $totales = (new CalculadoraComercial)->totalizar([
            ['descuento' => '0.10', 'subtotal' => '0.20', 'iva' => '0.03', 'total' => '0.23'],
            ['descuento' => '0.20', 'subtotal' => '0.10', 'iva' => '0.02', 'total' => '0.12'],
        ]);

        $this->assertSame(['subtotal' => '0.30', 'descuento' => '0.30', 'iva' => '0.05', 'total' => '0.35'], $totales);
    }
}
