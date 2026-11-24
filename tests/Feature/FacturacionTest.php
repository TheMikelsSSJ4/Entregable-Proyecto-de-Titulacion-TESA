<?php

namespace Tests\Feature;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoFactura;
use App\Enums\TipoTransaccion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\FacturacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturacionTest extends TestCase
{
    use RefreshDatabase;

    private Producto $arroz;

    private Producto $detergente;

    private Usuario $vendedorVenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arroz = Producto::factory()->precio('23.50', '0', '0')->create();
        $this->detergente = Producto::factory()->precio('21.30', '10', '15')->create();
        $this->vendedorVenta = Usuario::factory()->vendedor()->create();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosVenta(Cliente $cliente, array $cambios = []): array
    {
        return array_merge([
            'cliente_id' => $cliente->id,
            'vendedor_id' => $this->vendedorVenta->id,
            'numero_factura' => '001-001-000000101',
            'tipo_transaccion' => 'credito',
            'fecha_emision' => today()->toDateString(),
            'fecha_vencimiento' => today()->addDays(30)->toDateString(),
            'lineas' => [
                ['producto_id' => $this->arroz->id, 'cantidad' => '2'],
                ['producto_id' => $this->detergente->id, 'cantidad' => '3'],
            ],
        ], $cambios);
    }

    public function test_venta_a_credito_calcula_totales_desde_el_catalogo(): void
    {
        $vendedor = $this->vendedor();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)
            ->post(route('facturas.store'), $this->datosVenta($cliente, ['subtotal' => '1', 'iva' => '0', 'total' => '1']))
            ->assertRedirect();

        $factura = Factura::with('detalles')->sole();
        $this->assertSame(TipoTransaccion::Credito, $factura->tipo_transaccion);
        $this->assertSame(EstadoFactura::Pendiente, $factura->estado);
        $this->assertCount(2, $factura->detalles);
        // Arroz 2 × 23.50 = 47.00 (IVA 0%) · Detergente 3 × 21.30 = 63.90 − 6.39 = 57.51 + IVA 8.63
        $this->assertSame('104.51', $factura->subtotal);
        $this->assertSame('6.39', $factura->descuento);
        $this->assertSame('8.63', $factura->iva);
        $this->assertSame('113.14', $factura->total);
        $this->assertSame('113.14', $factura->saldo());
        $this->assertSame('Producto', substr($factura->detalles[0]->descripcion, 0, 8));
        $this->assertDatabaseCount('pagos_abonos', 0);
    }

    public function test_venta_al_contado_queda_pagada_con_pago_automatico(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->vendedor())->post(route('facturas.store'), $this->datosVenta($cliente, [
            'tipo_transaccion' => 'contado',
            'fecha_vencimiento' => null,
            'metodo_pago' => 'efectivo',
            'lineas' => [['producto_id' => $this->arroz->id, 'cantidad' => '1.5']],
        ]))->assertRedirect();

        $factura = Factura::sole();
        $this->assertSame(EstadoFactura::Pagada, $factura->estado);
        $this->assertSame('35.25', $factura->total);
        $this->assertTrue($factura->fecha_vencimiento->isSameDay($factura->fecha_emision));
        $this->assertSame('35.25', PagoAbono::sole()->monto_abonado);
    }

    public function test_cambiar_el_precio_del_catalogo_no_altera_facturas_emitidas(): void
    {
        $cliente = Cliente::factory()->create();
        $this->actingAs($this->gerente())->post(route('facturas.store'), $this->datosVenta($cliente));

        $this->arroz->update(['precio_base' => '99.00', 'precio_final' => '99.00']);

        $this->assertSame('23.50', Factura::sole()->detalles()->where('producto_id', $this->arroz->id)->sole()->precio_unitario);
    }

    public function test_rechaza_lineas_invalidas(): void
    {
        $cliente = Cliente::factory()->create();
        $inactivo = Producto::factory()->inactivo()->create();
        $this->actingAs($this->contador());

        $this->post(route('facturas.store'), $this->datosVenta($cliente, ['lineas' => []]))
            ->assertSessionHasErrors('lineas');

        $this->post(route('facturas.store'), $this->datosVenta($cliente, ['lineas' => [
            ['producto_id' => $this->arroz->id, 'cantidad' => '1'],
            ['producto_id' => $this->arroz->id, 'cantidad' => '2'],
        ]]))->assertSessionHasErrors('lineas.1.producto_id');

        $this->post(route('facturas.store'), $this->datosVenta($cliente, ['lineas' => [
            ['producto_id' => $inactivo->id, 'cantidad' => '1'],
        ]]))->assertSessionHasErrors('lineas.0.producto_id');

        $this->post(route('facturas.store'), $this->datosVenta($cliente, ['lineas' => [
            ['producto_id' => $this->arroz->id, 'cantidad' => '0'],
        ]]))->assertSessionHasErrors('lineas.0.cantidad');

        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_rechaza_numero_de_factura_con_formato_invalido_o_duplicado(): void
    {
        $cliente = Cliente::factory()->create();
        Factura::factory()->create(['numero_factura' => '001-001-000000101']);
        $this->actingAs($this->contador());

        $this->post(route('facturas.store'), $this->datosVenta($cliente, ['numero_factura' => 'FAC-001-2026']))
            ->assertSessionHasErrors('numero_factura');

        $this->post(route('facturas.store'), $this->datosVenta($cliente))
            ->assertSessionHasErrors('numero_factura');

        $this->assertDatabaseCount('facturas', 1);
    }

    public function test_credito_exige_vencimiento_posterior_a_la_emision(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->contador())
            ->post(route('facturas.store'), $this->datosVenta($cliente, ['fecha_vencimiento' => today()->toDateString()]))
            ->assertSessionHasErrors('fecha_vencimiento');
    }

    public function test_numero_sugerido_es_el_siguiente_secuencial(): void
    {
        Factura::factory()->create(['numero_factura' => '001-001-000000041']);

        $this->actingAs($this->vendedor())
            ->get(route('facturas.create'))
            ->assertOk()
            ->assertSee('001-001-000000042');
    }

    public function test_cotizacion_maestro_detalle_se_crea_y_sus_lineas_se_reemplazan_al_editar(): void
    {
        $vendedor = $this->vendedor();
        $cliente = Cliente::factory()->create();

        $this->actingAs($vendedor)->post(route('cotizaciones.store'), [
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'fecha_emision' => today()->toDateString(),
            'lineas' => [['producto_id' => $this->detergente->id, 'cantidad' => '3']],
        ])->assertRedirect();

        $cotizacion = Cotizacion::sole();
        $this->assertSame('66.14', $cotizacion->total);
        $this->assertSame(EstadoCotizacion::Pendiente, $cotizacion->estado);

        $this->put(route('cotizaciones.update', $cotizacion), [
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'fecha_emision' => today()->toDateString(),
            'estado' => 'aprobada',
            'lineas' => [['producto_id' => $this->arroz->id, 'cantidad' => '4']],
        ])->assertRedirect();

        $cotizacion->refresh();
        $this->assertSame('94.00', $cotizacion->total);
        $this->assertSame(EstadoCotizacion::Aprobada, $cotizacion->estado);
        $this->assertSame([$this->arroz->id], $cotizacion->detalles()->pluck('producto_id')->all());
    }

    public function test_facturar_cotizacion_copia_sus_lineas_y_la_marca_facturada(): void
    {
        $vendedor = $this->vendedor();
        $cliente = Cliente::factory()->create();
        $cotizacion = app(FacturacionService::class)->registrarCotizacion([
            'cliente_id' => $cliente->id,
            'fecha_emision' => today()->toDateString(),
            'lineas' => [['producto_id' => $this->detergente->id, 'cantidad' => '3']],
        ], $vendedor);

        $this->actingAs($vendedor)
            ->get(route('facturas.create', ['cotizacion' => $cotizacion->id]))
            ->assertOk()
            ->assertSee($cotizacion->numero);

        $this->post(route('facturas.store'), $this->datosVenta($cliente, [
            'cotizacion_id' => $cotizacion->id,
            'lineas' => app(FacturacionService::class)->lineasDesdeCotizacion($cotizacion),
        ]))->assertRedirect();

        $factura = Factura::sole();
        $this->assertSame($cotizacion->id, $factura->cotizacion_id);
        $this->assertSame($cotizacion->total, $factura->total);
        $this->assertSame(EstadoCotizacion::Facturada, $cotizacion->fresh()->estado);
    }

    public function test_cotizacion_facturada_no_se_factura_dos_veces(): void
    {
        $cotizacion = Cotizacion::factory()->facturada()->create();

        $this->actingAs($this->gerente())
            ->post(route('facturas.store'), $this->datosVenta($cotizacion->cliente, ['cotizacion_id' => $cotizacion->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_anula_factura_sin_abonos_y_rechaza_si_tiene_abonos(): void
    {
        $contador = $this->contador();
        $sinAbonos = Factura::factory()->create();
        $conAbonos = Factura::factory()->create(['estado' => EstadoFactura::Parcial]);
        PagoAbono::factory()->for($conAbonos)->create();

        $this->actingAs($contador)->patch(route('facturas.anular', $sinAbonos))->assertRedirect();
        $this->assertSame(EstadoFactura::Anulada, $sinAbonos->fresh()->estado);

        $this->patch(route('facturas.anular', $conAbonos))->assertSessionHas('error');
        $this->assertSame(EstadoFactura::Parcial, $conAbonos->fresh()->estado);
    }

    public function test_vendedor_no_puede_anular_facturas(): void
    {
        $vendedor = $this->vendedor();
        $factura = Factura::factory()->for($vendedor, 'usuario')->create();

        $this->actingAs($vendedor)->patch(route('facturas.anular', $factura))->assertForbidden();
    }
}
