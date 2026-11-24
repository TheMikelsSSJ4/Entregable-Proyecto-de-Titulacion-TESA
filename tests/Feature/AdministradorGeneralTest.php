<?php

namespace Tests\Feature;

use App\Enums\RolNombre;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministradorGeneralTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_accede_a_todos_los_modulos(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        foreach (['usuarios.index', 'empresa.edit', 'productos.create', 'cartera.index', 'reportes.index', 'abonos.create', 'cotizaciones.create'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    public function test_administrador_ve_facturas_de_cualquier_vendedor(): void
    {
        $factura = Factura::factory()->for($this->vendedor(), 'usuario')->create();

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->get(route('facturas.show', $factura))
            ->assertOk();
    }

    public function test_solo_el_administrador_configura_la_empresa(): void
    {
        $this->actingAs($this->gerente())->get(route('empresa.edit'))->assertForbidden();

        $this->actingAs(Usuario::factory()->administrador()->create())->put(route('empresa.update'), [
            'razon_social' => 'Privileg Distribuciones Ltda. S.A.',
            'ruc' => '1893947597001',
            'direccion' => 'Av. Cevallos, Ambato',
            'telefono' => '032420000',
            'correo' => 'FACTURACION@PRIVILEG.COM',
        ])->assertRedirect(route('empresa.edit'));

        $empresa = EmpresaConfiguracion::sole();
        $this->assertSame(EmpresaConfiguracion::ID_UNICO, $empresa->id);
        $this->assertSame('1893947597001', $empresa->ruc);
        $this->assertSame('facturacion@privileg.com', $empresa->correo);
    }

    public function test_rechaza_ruc_de_empresa_invalido(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())->put(route('empresa.update'), [
            'razon_social' => 'Privileg',
            'ruc' => '1836809234',
            'direccion' => 'Ambato',
        ])->assertSessionHasErrors('ruc');

        $this->assertDatabaseCount('empresa_configuracion', 0);
    }

    public function test_gerente_no_puede_crear_ni_editar_administradores(): void
    {
        $gerente = $this->gerente();
        $administrador = Usuario::factory()->administrador()->create();
        $rolAdministrador = Rol::firstWhere('nombre', RolNombre::Administrador->value);

        $this->actingAs($gerente)->post(route('usuarios.store'), [
            'nombre_completo' => 'Intento de escalada',
            'email' => 'escalada@privileg.com',
            'rol_id' => $rolAdministrador->id,
            'estado' => 'activo',
            'password' => 'Segura2026x',
            'password_confirmation' => 'Segura2026x',
        ])->assertSessionHasErrors('rol_id');

        $this->get(route('usuarios.edit', $administrador))->assertForbidden();
        $this->assertDatabaseMissing('usuarios', ['email' => 'escalada@privileg.com']);
    }
}
