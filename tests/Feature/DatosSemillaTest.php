<?php

namespace Tests\Feature;

use App\Enums\EstadoFactura;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CarteraService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que el seeder deja el sistema listo para pruebas y capturas,
 * y que cada pantalla principal carga con datos reales para cada rol.
 */
class DatosSemillaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_siembra_roles_usuarios_clientes_y_transacciones(): void
    {
        $this->assertSame(['Administrador General', 'Gerente', 'Contador', 'Vendedor'], Rol::orderBy('id')->get()->map(fn (Rol $r) => $r->nombre->value)->all());
        $this->assertTrue(Usuario::where('email', 'jmorellana@privileg.com')->sole()->esAdministrador());
        $this->assertSame(15, Producto::count());
        $this->assertSame('Privileg Distribuciones Ltda. S.A.', EmpresaConfiguracion::actual()->razon_social);
        $this->assertSame(0, Factura::query()->whereRaw('total <> (SELECT SUM(d.total) FROM factura_detalles d WHERE d.factura_id = facturas.id)')->count());
        $this->assertTrue(Usuario::where('email', 'jsantacruz@privileg.com')->exists());
        $this->assertSame(14, Cliente::count());
        $this->assertSame(6, Cotizacion::count());

        $this->assertTrue(Factura::where('tipo_transaccion', 'contado')->where('estado', EstadoFactura::Pagada->value)->exists());
        $this->assertTrue(Factura::where('estado', EstadoFactura::Parcial->value)->exists());
        $this->assertTrue(Factura::where('estado', EstadoFactura::Anulada->value)->exists());

        $resumen = app(CarteraService::class)->resumen();
        $this->assertGreaterThan(0, $resumen['facturas_vencidas']);
        $this->assertGreaterThan(0, $resumen['facturas_por_vencer']);
        foreach ($resumen['tramos'] as $tramo) {
            $this->assertGreaterThan(0, $tramo['cantidad'], "El tramo {$tramo['etiqueta']} no tiene facturas de ejemplo.");
        }
    }

    public function test_cuentas_sembradas_inician_sesion_con_la_contrasena_de_pruebas(): void
    {
        $this->post(route('login.store'), [
            'email' => 'ana.contador@privileg.com',
            'password' => DatabaseSeeder::USUARIOS_PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_pantallas_principales_cargan_para_gerente_y_contador(): void
    {
        foreach (['jmorellana@privileg.com', 'ana.contador@privileg.com'] as $email) {
            $usuario = Usuario::where('email', $email)->sole();
            $factura = Factura::where('estado', EstadoFactura::Parcial->value)->firstOrFail();
            $cliente = $factura->cliente;
            $cotizacion = Cotizacion::firstOrFail();

            $this->actingAs($usuario);

            foreach ([
                route('dashboard'),
                route('clientes.index'),
                route('clientes.show', $cliente),
                route('facturas.index'),
                route('facturas.create'),
                route('facturas.show', $factura),
                route('abonos.create'),
                route('documentos.index'),
                route('cotizaciones.show', $cotizacion),
                route('cartera.index'),
                route('productos.index'),
                route('reportes.index'),
            ] as $url) {
                $this->get($url)->assertOk();
            }
        }

        // Editar cotizaciones: Administrador sí; Contador no (sólo las emiten Vendedores y Gerencia).
        $pendiente = Cotizacion::where('estado', 'pendiente')->firstOrFail();
        $this->actingAs(Usuario::where('email', 'jmorellana@privileg.com')->sole())->get(route('cotizaciones.edit', $pendiente))->assertOk();
        $this->actingAs(Usuario::where('email', 'ana.contador@privileg.com')->sole())->get(route('cotizaciones.edit', $pendiente))->assertForbidden();
    }

    public function test_pantallas_del_vendedor_cargan(): void
    {
        $this->actingAs(Usuario::where('email', 'jsantacruz@privileg.com')->sole());

        foreach ([
            route('dashboard'),
            route('clientes.index'),
            route('facturas.index'),
            route('documentos.index'),
            route('cotizaciones.create'),
            route('facturas.create'),
            route('productos.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
