<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_registra_cliente_con_ruc_valido(): void
    {
        $this->actingAs($this->vendedor())->post(route('clientes.store'), [
            'ruc_cedula' => '1893-947597-001',
            'razon_social' => '  Comercial   Ortiz Cía. Ltda. ',
            'direccion' => 'Av. Cevallos y Montalvo, Ambato',
            'telefono' => '032412890',
            'correo' => 'COBRANZAS@ORTIZ.EC',
        ])->assertRedirect();

        $this->assertDatabaseHas('clientes', [
            'ruc_cedula' => '1893947597001',
            'razon_social' => 'Comercial Ortiz Cía. Ltda.',
            'correo' => 'cobranzas@ortiz.ec',
        ]);
    }

    public function test_rechaza_cedula_con_digito_verificador_invalido(): void
    {
        $this->actingAs($this->vendedor())->post(route('clientes.store'), [
            'ruc_cedula' => '1836809235',
            'razon_social' => 'Cliente con cédula errónea',
            'direccion' => 'Ambato',
        ])->assertSessionHasErrors('ruc_cedula');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rechaza_identificacion_duplicada(): void
    {
        Cliente::factory()->create(['ruc_cedula' => '1836809234']);

        $this->actingAs($this->contador())->post(route('clientes.store'), [
            'ruc_cedula' => '1836809234',
            'razon_social' => 'Duplicado',
            'direccion' => 'Ambato',
        ])->assertSessionHasErrors('ruc_cedula');
    }

    public function test_busqueda_filtra_por_razon_social_sin_distinguir_mayusculas(): void
    {
        Cliente::factory()->create(['razon_social' => 'Ferretería El Volcán']);
        Cliente::factory()->create(['razon_social' => 'Panificadora Atocha']);

        $this->actingAs($this->contador())
            ->get(route('clientes.index', ['buscar' => 'volcán']))
            ->assertOk()
            ->assertSee('Ferretería El Volcán')
            ->assertDontSee('Panificadora Atocha');
    }

    public function test_gerente_no_elimina_cliente_con_facturas(): void
    {
        $factura = Factura::factory()->create();

        $this->actingAs($this->gerente())
            ->from(route('clientes.show', $factura->cliente))
            ->delete(route('clientes.destroy', $factura->cliente))
            ->assertRedirect(route('clientes.show', $factura->cliente))
            ->assertSessionHas('error');

        $this->assertModelExists($factura->cliente);
    }

    public function test_gerente_elimina_cliente_sin_documentos(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->gerente())
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertModelMissing($cliente);
    }
}
