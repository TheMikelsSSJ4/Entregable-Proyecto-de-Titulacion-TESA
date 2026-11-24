<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Filas por página en los listados, tema claro/oscuro y vista previa de reportes.
 */
class PaginacionTemaYVistaPreviaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function periodo(): array
    {
        return [
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ];
    }

    public function test_listados_muestran_10_filas_por_defecto_y_permiten_elegir_otra_cantidad(): void
    {
        Cliente::factory()->count(25)->create();
        $this->actingAs($this->contador());

        $this->get(route('clientes.index'))
            ->assertOk()
            ->assertViewHas('clientes', fn (LengthAwarePaginator $clientes): bool => $clientes->perPage() === 10)
            ->assertSee('Filas por página')
            ->assertSee('data-filas-por-pagina', false);

        $this->get(route('clientes.index', ['por_pagina' => 20]))
            ->assertOk()
            ->assertViewHas('clientes', fn (LengthAwarePaginator $clientes): bool => $clientes->perPage() === 20 && $clientes->count() === 20)
            ->assertSessionHas('filas_por_pagina', 20);

        // La elección se recuerda en la sesión y aplica a los demás listados.
        $this->get(route('facturas.index'))
            ->assertViewHas('facturas', fn (LengthAwarePaginator $facturas): bool => $facturas->perPage() === 20);
    }

    public function test_ignora_cantidades_de_filas_no_permitidas(): void
    {
        $this->actingAs($this->gerente())
            ->get(route('productos.index', ['por_pagina' => 1000]))
            ->assertOk()
            ->assertViewHas('productos', fn (LengthAwarePaginator $productos): bool => $productos->perPage() === 10)
            ->assertSessionMissing('filas_por_pagina');
    }

    public function test_selector_de_filas_conserva_los_filtros_y_vuelve_a_la_primera_pagina(): void
    {
        Cliente::factory()->count(12)->sequence(fn ($secuencia): array => ['razon_social' => "Ferretería Ambato {$secuencia->index}"])->create();

        $this->actingAs($this->contador())
            ->get(route('clientes.index', ['buscar' => 'Ambato', 'page' => 2]))
            ->assertOk()
            ->assertSee('clientes?buscar=Ambato&amp;por_pagina=50', false);
    }

    public function test_usuarios_muestra_el_selector_de_filas_aunque_todo_quepa_en_una_pagina(): void
    {
        $gerente = $this->gerente();

        $this->actingAs($gerente)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertViewHas('usuarios', fn (LengthAwarePaginator $usuarios): bool => $usuarios->total() < 10)
            ->assertSee('Filas por página')
            ->assertSee('data-filas-por-pagina', false);

        $this->get(route('usuarios.index', ['por_pagina' => 50]))
            ->assertViewHas('usuarios', fn (LengthAwarePaginator $usuarios): bool => $usuarios->perPage() === 50);
    }

    public function test_selector_de_filas_no_aparece_en_un_listado_vacio(): void
    {
        $this->actingAs($this->contador())
            ->get(route('productos.index'))
            ->assertOk()
            ->assertDontSee('data-filas-por-pagina', false);
    }

    public function test_tema_oscuro_por_defecto_y_claro_segun_la_preferencia_guardada(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-tema="oscuro"', false)
            ->assertSee('data-selector-tema', false)
            ->assertSee('Cambiar a tema claro');

        $this->withUnencryptedCookie('privileg_tema', 'claro')
            ->get(route('login'))
            ->assertSee('data-tema="claro"', false)
            ->assertSee('<meta name="color-scheme" content="light">', false)
            ->assertSee('Cambiar a tema oscuro');
    }

    public function test_tema_ignora_valores_desconocidos_en_la_cookie(): void
    {
        $this->withUnencryptedCookie('privileg_tema', '"><script>alert(1)</script>')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('data-tema="oscuro"', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_layout_principal_aplica_el_tema_elegido(): void
    {
        $this->actingAs($this->contador())
            ->withUnencryptedCookie('privileg_tema', 'claro')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-tema="claro"', false)
            ->assertSee('x-data="selectorTema"', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function reportes(): array
    {
        return [
            'general' => ['general', 'Reporte general del periodo'],
            'ventas' => ['ventas', 'Reporte general de ventas y transacciones'],
            'ventas por vendedor' => ['vendedores', 'Vendedor'],
            'cartera' => ['cartera', 'Cartera'],
            'estado de cuenta' => ['estado-cuenta', 'Estado de cuenta'],
        ];
    }

    #[DataProvider('reportes')]
    public function test_vista_previa_en_pdf_muestra_cada_reporte_en_un_marco_propio(string $tipo, string $texto): void
    {
        $factura = Factura::factory()->vencida(10)->create();

        $respuesta = $this->actingAs($this->contador())->get(route('reportes.vista-previa', [
            'tipo' => $tipo,
            'formato' => 'pdf',
            'fecha_inicio' => today()->subMonths(3)->toDateString(),
            'fecha_fin' => today()->toDateString(),
            'cliente_id' => $factura->cliente_id,
        ]));

        $respuesta->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee($texto);

        // El general resume por vendedor y cliente; los demás listan cada factura.
        $respuesta->assertSee($tipo === 'general' ? $factura->cliente->razon_social : $factura->numero_factura);

        $csp = (string) $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("default-src 'none'", $csp);
    }

    #[DataProvider('reportes')]
    public function test_vista_previa_en_excel_muestra_las_hojas_del_libro(string $tipo): void
    {
        $factura = Factura::factory()->vencida(10)->create();

        $this->actingAs($this->contador())->get(route('reportes.vista-previa', [
            'tipo' => $tipo,
            'formato' => 'xlsx',
            'fecha_inicio' => today()->subMonths(3)->toDateString(),
            'fecha_fin' => today()->toDateString(),
            'cliente_id' => $factura->cliente_id,
        ]))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('Hojas del libro')
            ->assertSee('class="hoja"', false)
            ->assertSee($factura->cliente->razon_social)
            ->assertSee('$115.00');
    }

    public function test_vista_previa_en_excel_respeta_el_contenido_de_cada_hoja(): void
    {
        $vendedor = Usuario::factory()->vendedor()->create(['nombre_completo' => 'Ana Vendedora']);
        Factura::factory()->count(2)->create(['usuario_id' => $vendedor->id, 'vendedor_id' => $vendedor->id]);
        Cliente::factory()->create(['razon_social' => '<script>alert(1)</script>']);

        $html = $this->actingAs($this->gerente())->get(route('reportes.vista-previa', [
            'tipo' => 'vendedores', 'formato' => 'xlsx', ...$this->periodo(),
        ]))
            ->assertOk()
            ->assertSee('Por vendedor')
            ->assertSee('Detalle')
            ->assertSee('Ana Vendedora')
            ->assertSee('$230.00')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'class="hoja"'));
    }

    public function test_vista_previa_muestra_los_errores_de_los_filtros_dentro_del_marco(): void
    {
        $this->actingAs($this->contador())
            ->get(route('reportes.vista-previa', ['tipo' => 'estado-cuenta', 'formato' => 'xlsx', ...$this->periodo()]))
            ->assertStatus(422)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('No se puede mostrar la vista previa')
            ->assertSee('Seleccione el cliente para generar su estado de cuenta.');
    }

    public function test_vendedor_no_accede_a_la_vista_previa(): void
    {
        $this->actingAs($this->vendedor())
            ->get(route('reportes.vista-previa', ['tipo' => 'ventas', 'formato' => 'pdf', ...$this->periodo()]))
            ->assertForbidden();
    }

    public function test_cada_reporte_tiene_su_vista_previa_y_la_ventana_permite_pdf_o_excel(): void
    {
        $html = $this->actingAs($this->contador())
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertSee('data-vista-previa-general', false)
            ->assertSee('name="marco-vista-previa"', false)
            ->assertSee('x-on:click="abrir($el.dataset.tipo, $el.dataset.titulo)"', false)
            ->assertSee(route('reportes.vista-previa', ['tipo' => 'general', 'formato' => 'pdf']))
            ->assertSee(route('reportes.vista-previa', ['tipo' => 'estado-cuenta', 'formato' => 'pdf']))
            ->assertSee(route('reportes.vista-previa', ['tipo' => 'general', 'formato' => 'xlsx']))
            ->assertSee('Reporte general del periodo')
            // Las directivas de Blade dentro de componentes no se procesan: no deben llegar al navegador.
            ->assertDontSee('@js(', false)
            ->getContent();

        // Botón de vista previa en las 5 tarjetas, 5 pestañas y el selector PDF | Excel.
        // (Los componentes de Blade escriben el atributo como data-x="data-x".)
        $this->assertSame(5, substr_count($html, 'data-vista-previa-reporte='));
        $this->assertSame(5, substr_count($html, 'data-pestana-vista-previa'));
        $this->assertSame(2, substr_count($html, 'data-formato-vista-previa'));
    }

    public function test_reporte_general_en_excel_resume_ventas_vendedores_y_cartera(): void
    {
        $vendedor = $this->vendedor();
        Factura::factory()->vencida(40)->create(['usuario_id' => $vendedor->id, 'vendedor_id' => $vendedor->id]);

        $respuesta = $this->actingAs($this->gerente())->get(route('reportes.exportar', [
            'tipo' => 'general', 'formato' => 'xlsx',
            'fecha_inicio' => today()->subMonths(3)->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ]));

        $respuesta->assertOk();
        $this->assertStringContainsString('Reporte-General-', (string) $respuesta->headers->get('content-disposition'));

        $libro = IOFactory::load($respuesta->baseResponse->getFile()->getPathname());
        $this->assertSame(['Resumen', 'Por vendedor', 'Antigüedad', 'Principales deudores'], $libro->getSheetNames());

        $resumen = $libro->getSheetByName('Resumen');
        $this->assertSame('Total vendido', $resumen->getCell('B7')->getValue());
        $this->assertSame(115.0, (float) $resumen->getCell('C7')->getValue());

        $this->assertSame($vendedor->nombre_completo, $libro->getSheetByName('Por vendedor')->getCell('A7')->getValue());
        // 40 días de mora: tramo 31 – 60.
        $this->assertSame(115.0, (float) $libro->getSheetByName('Antigüedad')->getCell('C8')->getValue());
    }
}
