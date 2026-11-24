<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Services\FacturacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerente_registra_producto_y_el_servidor_calcula_el_precio_final(): void
    {
        $this->actingAs($this->gerente())->post(route('productos.store'), [
            'codigo' => ' abr-010 ',
            'nombre' => 'Arroz blanco  25 kg',
            'precio_base' => '10,00',
            'porcentaje_descuento' => '5',
            'porcentaje_iva' => '15',
            'precio_final' => '1.00',
            'activo' => '1',
        ])->assertRedirect(route('productos.index'));

        $producto = Producto::sole();
        $this->assertSame('ABR-010', $producto->codigo);
        $this->assertSame('Arroz blanco 25 kg', $producto->nombre);
        $this->assertSame('10.93', $producto->precio_final);
        $this->assertTrue($producto->activo);
    }

    public function test_valida_porcentajes_y_codigo_unico(): void
    {
        Producto::factory()->create(['codigo' => 'ABR-001']);

        $this->actingAs($this->gerente())->post(route('productos.store'), [
            'codigo' => 'ABR-001',
            'nombre' => 'Duplicado',
            'precio_base' => '10',
            'porcentaje_descuento' => '120',
            'porcentaje_iva' => '12',
        ])->assertSessionHasErrors(['codigo', 'porcentaje_descuento', 'porcentaje_iva']);
    }

    public function test_vendedor_y_contador_consultan_el_catalogo_pero_no_lo_modifican(): void
    {
        $producto = Producto::factory()->create();

        foreach ([$this->vendedor(), $this->contador()] as $usuario) {
            $this->actingAs($usuario);

            $this->get(route('productos.index'))->assertOk()->assertSee($producto->codigo);
            $this->get(route('productos.create'))->assertForbidden();
            $this->put(route('productos.update', $producto), [])->assertForbidden();
            $this->delete(route('productos.destroy', $producto))->assertForbidden();
        }
    }

    public function test_actualizar_recalcula_el_precio_final(): void
    {
        $producto = Producto::factory()->create();

        $this->actingAs($this->gerente())->put(route('productos.update', $producto), [
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'precio_base' => '20.00',
            'porcentaje_descuento' => '0',
            'porcentaje_iva' => '0',
            'activo' => '0',
        ])->assertRedirect(route('productos.index'));

        $producto->refresh();
        $this->assertSame('20.00', $producto->precio_final);
        $this->assertFalse($producto->activo);
    }

    public function test_no_elimina_producto_usado_en_documentos(): void
    {
        $gerente = $this->gerente();
        $usado = Producto::factory()->create();
        $libre = Producto::factory()->create();
        app(FacturacionService::class)->registrarCotizacion([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha_emision' => today()->toDateString(),
            'lineas' => [['producto_id' => $usado->id, 'cantidad' => '1']],
        ], $gerente);

        $this->actingAs($gerente)
            ->delete(route('productos.destroy', $usado))
            ->assertSessionHas('error');
        $this->assertModelExists($usado);

        $this->delete(route('productos.destroy', $libre))->assertRedirect(route('productos.index'));
        $this->assertModelMissing($libre);
    }
}
