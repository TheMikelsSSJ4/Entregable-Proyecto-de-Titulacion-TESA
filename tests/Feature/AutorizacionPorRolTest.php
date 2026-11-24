<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\PagoAbono;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacionPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_no_accede_a_modulos_financieros_ni_de_seguridad(): void
    {
        $this->actingAs($this->vendedor());

        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('cartera.index'))->assertForbidden();
        $this->get(route('reportes.index'))->assertForbidden();
        $this->get(route('empresa.edit'))->assertForbidden();
        $this->get(route('abonos.create'))->assertForbidden();
    }

    public function test_vendedor_no_ve_facturas_ni_cotizaciones_de_otro_vendedor(): void
    {
        $facturaAjena = Factura::factory()->create();
        $cotizacionAjena = Cotizacion::factory()->create();

        $this->actingAs($this->vendedor());

        $this->get(route('facturas.show', $facturaAjena))->assertForbidden();
        $this->get(route('facturas.pdf', $facturaAjena))->assertForbidden();
        $this->get(route('cotizaciones.show', $cotizacionAjena))->assertForbidden();
    }

    public function test_listado_de_transacciones_del_vendedor_solo_incluye_las_suyas(): void
    {
        $vendedor = $this->vendedor();
        $propia = Factura::factory()->for($vendedor, 'usuario')->create();
        $ajena = Factura::factory()->create();

        $this->actingAs($vendedor)
            ->get(route('facturas.index'))
            ->assertOk()
            ->assertSee($propia->numero_factura)
            ->assertDontSee($ajena->numero_factura);
    }

    public function test_contador_accede_a_cartera_pero_no_a_usuarios_ni_cotizaciones_nuevas(): void
    {
        $this->actingAs($this->contador());

        $this->get(route('cartera.index'))->assertOk();
        $this->get(route('abonos.create'))->assertOk();
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('cotizaciones.create'))->assertForbidden();
    }

    public function test_solo_gerencia_revierte_abonos_y_elimina_clientes(): void
    {
        $pago = PagoAbono::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->contador());

        $this->delete(route('abonos.destroy', $pago))->assertForbidden();
        $this->delete(route('clientes.destroy', $cliente))->assertForbidden();

        $this->assertModelExists($pago);
        $this->assertModelExists($cliente);
    }

    public function test_gerente_administra_usuarios(): void
    {
        $this->actingAs($this->gerente());

        $this->get(route('usuarios.index'))->assertOk();
        $this->get(route('usuarios.create'))->assertOk();
    }
}
