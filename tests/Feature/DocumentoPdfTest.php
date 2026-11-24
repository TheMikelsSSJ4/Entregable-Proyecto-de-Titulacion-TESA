<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentoPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_descarga_pdf_de_factura(): void
    {
        $factura = Factura::factory()->vencida(5)->create();

        $respuesta = $this->actingAs($this->contador())->get(route('facturas.pdf', $factura));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertStringContainsString($factura->numero_factura, $respuesta->headers->get('content-disposition'));
    }

    public function test_vendedor_descarga_pdf_de_su_cotizacion(): void
    {
        $vendedor = $this->vendedor();
        $cotizacion = Cotizacion::factory()->for($vendedor, 'usuario')->create();

        $this->actingAs($vendedor)
            ->get(route('cotizaciones.pdf', $cotizacion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_imprimir_abre_el_pdf_en_el_navegador(): void
    {
        $vendedor = $this->vendedor();
        $cotizacion = Cotizacion::factory()->for($vendedor, 'usuario')->create();

        $respuesta = $this->actingAs($vendedor)->get(route('cotizaciones.pdf', ['cotizacion' => $cotizacion, 'imprimir' => 1]));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline', (string) $respuesta->headers->get('content-disposition'));
    }

    public function test_historial_de_documentos_une_facturas_y_cotizaciones(): void
    {
        $cliente = Cliente::factory()->create(['razon_social' => 'Distribuidora Ambato S.A.']);
        $factura = Factura::factory()->for($cliente)->create();
        $cotizacion = Cotizacion::factory()->for($cliente)->create();

        $this->actingAs($this->contador())
            ->get(route('documentos.index'))
            ->assertOk()
            ->assertSee($factura->numero_factura)
            ->assertSee($cotizacion->numero);

        $this->get(route('documentos.index', ['tipo' => 'cotizaciones']))
            ->assertOk()
            ->assertSee($cotizacion->numero)
            ->assertDontSee($factura->numero_factura);
    }
}
