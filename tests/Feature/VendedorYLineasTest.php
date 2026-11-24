<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\FacturacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Vendedor de la venta, descuento/IVA manual por línea, "guardar en catálogo",
 * reporte por vendedor y menú lateral.
 */
class VendedorYLineasTest extends TestCase
{
    use RefreshDatabase;

    private Producto $detergente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detergente = Producto::factory()->precio('21.30', '10', '15')->create();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @param  array<string, mixed>  $linea
     * @return array<string, mixed>
     */
    private function venta(Cliente $cliente, Usuario $vendedor, array $linea = [], array $cambios = []): array
    {
        return array_merge([
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'numero_factura' => '001-001-000000500',
            'tipo_transaccion' => 'credito',
            'fecha_emision' => today()->toDateString(),
            'fecha_vencimiento' => today()->addDays(30)->toDateString(),
            'lineas' => [array_merge(['producto_id' => $this->detergente->id, 'cantidad' => '3'], $linea)],
        ], $cambios);
    }

    public function test_la_factura_guarda_el_vendedor_aparte_de_quien_la_registra(): void
    {
        $contadora = $this->contador();
        $vendedor = Usuario::factory()->vendedor()->create(['nombre_completo' => 'José Miguel Orellana Romo']);

        $this->actingAs($contadora)
            ->post(route('facturas.store'), $this->venta(Cliente::factory()->create(), $vendedor))
            ->assertRedirect();

        $factura = Factura::sole();
        $this->assertSame($vendedor->id, $factura->vendedor_id);
        $this->assertSame($contadora->id, $factura->usuario_id);

        $this->get(route('facturas.show', $factura))
            ->assertOk()
            ->assertSee('JOSÉ MIGUEL ORELLANA ROMO')
            ->assertSee($contadora->nombre_completo);
    }

    public function test_cualquier_usuario_puede_elegir_otro_vendedor_activo(): void
    {
        $vendedor = $this->vendedor();
        $otro = $this->gerente();
        $inactivo = Usuario::factory()->vendedor()->inactivo()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)
            ->post(route('facturas.store'), $this->venta($cliente, $otro))
            ->assertRedirect();
        $this->assertSame($otro->id, Factura::sole()->vendedor_id);

        $this->post(route('facturas.store'), $this->venta($cliente, $inactivo, [], ['numero_factura' => '001-001-000000501']))
            ->assertSessionHasErrors('vendedor_id');
    }

    public function test_el_vendedor_ve_las_facturas_que_vendio_aunque_otro_las_registre(): void
    {
        $vendedor = $this->vendedor();
        $factura = Factura::factory()->create(['usuario_id' => $this->contador()->id, 'vendedor_id' => $vendedor->id]);
        $ajena = Factura::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('facturas.index'))
            ->assertOk()
            ->assertSee($factura->numero_factura)
            ->assertDontSee($ajena->numero_factura);

        $this->get(route('facturas.show', $factura))->assertOk();
    }

    public function test_formulario_preselecciona_al_usuario_como_vendedor(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)
            ->get(route('facturas.create'))
            ->assertOk()
            ->assertSee('name="vendedor_id"', false)
            ->assertSee('<option value="'.$vendedor->id.'" selected', false);
    }

    public function test_descuento_manual_e_iva_de_la_lista_se_aplican_a_la_linea(): void
    {
        $this->actingAs($this->vendedor())->post(route('facturas.store'), $this->venta(
            Cliente::factory()->create(),
            $this->vendedor(),
            ['porcentaje_descuento' => '20', 'porcentaje_iva' => '0'],
        ))->assertRedirect();

        // 3 × 21.30 = 63.90 − 20% (12.78) = 51.12, IVA 0%
        $factura = Factura::with('detalles')->sole();
        $this->assertSame('20.00', $factura->detalles[0]->porcentaje_descuento);
        $this->assertSame('0.00', $factura->detalles[0]->porcentaje_iva);
        $this->assertSame('12.78', $factura->descuento);
        $this->assertSame('51.12', $factura->total);

        // El catálogo no cambia si no se marca la casilla.
        $this->assertSame('10.00', $this->detergente->fresh()->porcentaje_descuento);
    }

    public function test_rechaza_iva_fuera_de_la_lista_y_descuento_mayor_al_100(): void
    {
        $this->actingAs($this->vendedor())
            ->post(route('facturas.store'), $this->venta(Cliente::factory()->create(), $this->vendedor(), [
                'porcentaje_descuento' => '101',
                'porcentaje_iva' => '12',
            ]))
            ->assertSessionHasErrors(['lineas.0.porcentaje_descuento', 'lineas.0.porcentaje_iva']);

        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_gerente_guarda_el_ajuste_tambien_en_el_catalogo(): void
    {
        $gerente = $this->gerente();

        $this->actingAs($gerente)->post(route('facturas.store'), $this->venta(Cliente::factory()->create(), $gerente, [
            'porcentaje_descuento' => '5',
            'porcentaje_iva' => '15',
            'actualizar_catalogo' => '1',
        ]))->assertRedirect();

        $producto = $this->detergente->fresh();
        $this->assertSame('5.00', $producto->porcentaje_descuento);
        // 21.30 − 5% (1.07) = 20.23 + IVA 15% (3.03) = 23.26
        $this->assertSame('23.26', $producto->precio_final);
    }

    public function test_vendedor_no_puede_guardar_cambios_en_el_catalogo(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)
            ->post(route('facturas.store'), $this->venta(Cliente::factory()->create(), $vendedor, [
                'porcentaje_descuento' => '50',
                'actualizar_catalogo' => '1',
            ]))
            ->assertSessionHasErrors('lineas.0.actualizar_catalogo');

        $this->assertSame('10.00', $this->detergente->fresh()->porcentaje_descuento);
        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_precio_unitario_manual_se_aplica_a_la_linea(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)->post(route('facturas.store'), $this->venta(
            Cliente::factory()->create(),
            $vendedor,
            ['precio_unitario' => '25,00'],
        ))->assertRedirect();

        // 3 × 25.00 = 75.00 − 10% (7.50) = 67.50 + IVA 15% (10.13) = 77.63
        $detalle = Factura::with('detalles')->sole()->detalles[0];
        $this->assertSame('25.00', $detalle->precio_unitario);
        $this->assertSame('7.50', $detalle->descuento);
        $this->assertSame('10.13', $detalle->iva);
        $this->assertSame('77.63', $detalle->total);

        // Sin la casilla, el catálogo conserva su precio.
        $this->assertSame('21.30', $this->detergente->fresh()->precio_base);
    }

    public function test_rechaza_precio_unitario_cero_o_con_mas_de_dos_decimales(): void
    {
        $vendedor = $this->vendedor();
        $cliente = Cliente::factory()->create();
        $this->actingAs($vendedor);

        $this->post(route('facturas.store'), $this->venta($cliente, $vendedor, ['precio_unitario' => '0']))
            ->assertSessionHasErrors(['lineas.0.precio_unitario' => 'El precio unitario debe ser mayor que cero.']);

        $this->post(route('facturas.store'), $this->venta($cliente, $vendedor, ['precio_unitario' => '10.555']))
            ->assertSessionHasErrors('lineas.0.precio_unitario');

        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_gerente_guarda_el_precio_manual_en_el_catalogo(): void
    {
        $gerente = $this->gerente();

        $this->actingAs($gerente)->post(route('facturas.store'), $this->venta(Cliente::factory()->create(), $gerente, [
            'precio_unitario' => '30',
            'actualizar_catalogo' => '1',
        ]))->assertRedirect();

        $producto = $this->detergente->fresh();
        $this->assertSame('30.00', $producto->precio_base);
        // 30.00 − 10% (3.00) = 27.00 + IVA 15% (4.05) = 31.05
        $this->assertSame('31.05', $producto->precio_final);
    }

    public function test_el_historial_de_documentos_muestra_al_vendedor_lo_que_vendio(): void
    {
        $vendedor = $this->vendedor();
        $vendida = Factura::factory()->create(['usuario_id' => $this->contador()->id, 'vendedor_id' => $vendedor->id]);
        $ajena = Factura::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('documentos.index'))
            ->assertOk()
            ->assertSee($vendida->numero_factura)
            ->assertDontSee($ajena->numero_factura);
    }

    public function test_facturar_una_cotizacion_conserva_vendedor_precio_descuento_e_iva(): void
    {
        $vendedor = $this->vendedor();
        $cotizacion = app(FacturacionService::class)->registrarCotizacion([
            'cliente_id' => Cliente::factory()->create()->id,
            'vendedor_id' => $vendedor->id,
            'fecha_emision' => today()->toDateString(),
            'lineas' => [['producto_id' => $this->detergente->id, 'cantidad' => '2', 'precio_unitario' => '19.99', 'porcentaje_descuento' => '15', 'porcentaje_iva' => '5']],
        ], $this->gerente());

        $this->assertSame($vendedor->id, $cotizacion->vendedor_id);
        $this->assertSame(
            [['producto_id' => $this->detergente->id, 'cantidad' => '2.00', 'precio_unitario' => '19.99', 'porcentaje_descuento' => '15.00', 'porcentaje_iva' => '5']],
            app(FacturacionService::class)->lineasDesdeCotizacion($cotizacion),
        );

        $this->actingAs($vendedor)
            ->get(route('facturas.create', ['cotizacion' => $cotizacion->id]))
            ->assertOk()
            ->assertSee('<option value="'.$vendedor->id.'" selected', false);
    }

    public function test_reporte_por_vendedor_resume_las_ventas_de_cada_uno(): void
    {
        $ana = Usuario::factory()->vendedor()->create(['nombre_completo' => 'Ana Vendedora']);
        $luis = Usuario::factory()->vendedor()->create(['nombre_completo' => 'Luis Vendedor']);
        Factura::factory()->count(2)->create(['usuario_id' => $ana->id, 'vendedor_id' => $ana->id]);
        Factura::factory()->create(['usuario_id' => $luis->id, 'vendedor_id' => $luis->id]);

        $respuesta = $this->actingAs($this->gerente())->get(route('reportes.exportar', [
            'tipo' => 'vendedores', 'formato' => 'xlsx',
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ]));

        $respuesta->assertOk();
        $hoja = IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getSheetByName('Por vendedor');

        $this->assertSame('Ana Vendedora', $hoja->getCell('A7')->getValue());
        $this->assertSame(2, (int) $hoja->getCell('C7')->getValue());
        $this->assertSame(230.0, (float) $hoja->getCell('D7')->getValue());
        $this->assertSame('Luis Vendedor', $hoja->getCell('A8')->getValue());
        $this->assertSame(345.0, (float) $hoja->getCell('D9')->getValue());
    }

    public function test_reporte_de_ventas_filtrado_por_vendedor(): void
    {
        $ana = $this->vendedor();
        $propia = Factura::factory()->create(['usuario_id' => $ana->id, 'vendedor_id' => $ana->id]);
        $ajena = Factura::factory()->create();

        $respuesta = $this->actingAs($this->contador())->get(route('reportes.exportar', [
            'tipo' => 'ventas', 'formato' => 'xlsx', 'vendedor_id' => $ana->id,
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ]));

        $respuesta->assertOk();
        $this->assertStringContainsString(Str::slug($ana->nombre_completo), (string) $respuesta->headers->get('content-disposition'));

        $hoja = IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getSheetByName('Ventas');
        $numeros = array_column($hoja->toArray(), 0);
        $this->assertContains($propia->numero_factura, $numeros);
        $this->assertNotContains($ajena->numero_factura, $numeros);
        $this->assertStringContainsString(Str::upper($ana->nombre_completo), (string) $hoja->getCell('A4')->getValue());
    }

    public function test_el_layout_incluye_el_menu_desplegable_con_opcion_de_fijarlo(): void
    {
        $this->actingAs($this->contador())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('x-data="menuLateral"', false)
            ->assertSee('data-expandido="false"', false)
            ->assertSee('alternarFijo()', false)
            ->assertSee('Menú:');
    }

    public function test_cotizacion_muestra_su_vendedor(): void
    {
        $vendedor = Usuario::factory()->vendedor()->create(['nombre_completo' => 'Juan Esteban Santacruz Erazo']);
        $cotizacion = Cotizacion::factory()->create(['vendedor_id' => $vendedor->id]);

        $this->actingAs($this->gerente())
            ->get(route('cotizaciones.show', $cotizacion))
            ->assertOk()
            ->assertSee('JUAN ESTEBAN SANTACRUZ ERAZO');
    }
}
