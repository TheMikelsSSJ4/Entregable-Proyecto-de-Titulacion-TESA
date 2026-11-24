<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Models\Factura;
use App\Models\PagoAbono;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function reportes(): array
    {
        return [
            'general pdf' => ['general', 'pdf'],
            'general excel' => ['general', 'xlsx'],
            'ventas pdf' => ['ventas', 'pdf'],
            'ventas excel' => ['ventas', 'xlsx'],
            'ventas por vendedor pdf' => ['vendedores', 'pdf'],
            'ventas por vendedor excel' => ['vendedores', 'xlsx'],
            'cartera pdf' => ['cartera', 'pdf'],
            'cartera excel' => ['cartera', 'xlsx'],
            'estado de cuenta pdf' => ['estado-cuenta', 'pdf'],
            'estado de cuenta excel' => ['estado-cuenta', 'xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function periodo(): array
    {
        return [
            'fecha_inicio' => today()->subMonths(6)->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ];
    }

    #[DataProvider('reportes')]
    public function test_descarga_cada_reporte_en_pdf_y_excel(string $tipo, string $formato): void
    {
        $factura = Factura::factory()->vencida(20)->create();
        PagoAbono::factory()->for($factura)->create(['monto_abonado' => '15.00']);

        $respuesta = $this->actingAs($this->contador())->get(route('reportes.exportar', [
            'tipo' => $tipo,
            'formato' => $formato,
            ...$this->periodo(),
            'cliente_id' => $factura->cliente_id,
        ]));

        $respuesta->assertOk();
        $this->assertStringContainsString(".{$formato}", (string) $respuesta->headers->get('content-disposition'));

        if ($formato === 'pdf') {
            $respuesta->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        }
    }

    public function test_excel_incluye_cabecera_de_empresa_y_montos_numericos(): void
    {
        (new EmpresaConfiguracion)->forceFill(['id' => 1])->fill([
            'razon_social' => 'Privileg Distribuciones Ltda. S.A.',
            'ruc' => '1891700000001',
            'direccion' => 'Ambato',
        ])->save();
        $factura = Factura::factory()->vencida(10)->create();

        $libro = $this->libro($this->actingAs($this->gerente())->get(route('reportes.exportar', [
            'tipo' => 'cartera', 'formato' => 'xlsx', ...$this->periodo(),
        ])));

        $hoja = $libro->getSheetByName('Cartera');
        $this->assertSame('Privileg Distribuciones Ltda. S.A.', $hoja->getCell('A1')->getValue());
        $this->assertStringContainsString('RUC 1891700000001', (string) $hoja->getCell('A2')->getValue());
        $this->assertSame('Cliente', $hoja->getCell('A6')->getValue());
        $this->assertSame($factura->numero_factura, $hoja->getCell('C7')->getValue());
        $this->assertSame(115.0, (float) $hoja->getCell('H7')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $hoja->getCell('H7')->getDataType());
        $this->assertNotNull($libro->getSheetByName('Antigüedad'));
    }

    public function test_excel_no_ejecuta_formulas_inyectadas_en_datos(): void
    {
        $cliente = Cliente::factory()->create(['razon_social' => '=HYPERLINK("http://malicioso")']);
        Factura::factory()->for($cliente)->create();

        $libro = $this->libro($this->actingAs($this->gerente())->get(route('reportes.exportar', [
            'tipo' => 'ventas', 'formato' => 'xlsx', ...$this->periodo(),
        ])));

        $celda = $libro->getSheetByName('Ventas')->getCell('C7');
        $this->assertSame(DataType::TYPE_STRING, $celda->getDataType());
        $this->assertSame('=HYPERLINK("http://malicioso")', $celda->getValue());
    }

    public function test_valida_rango_de_fechas_y_cliente_del_estado_de_cuenta(): void
    {
        $this->actingAs($this->contador())->from(route('reportes.index'));

        $this->get(route('reportes.exportar', [
            'tipo' => 'ventas', 'formato' => 'pdf',
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->subDay()->toDateString(),
        ]))->assertRedirect(route('reportes.index'))->assertSessionHasErrors('fecha_fin');

        $this->get(route('reportes.exportar', ['tipo' => 'estado-cuenta', 'formato' => 'xlsx', ...$this->periodo()]))
            ->assertSessionHasErrors('cliente_id');
    }

    public function test_ventas_del_periodo_excluyen_facturas_fuera_de_rango(): void
    {
        $dentro = Factura::factory()->create(['fecha_emision' => today()->subDays(5), 'fecha_vencimiento' => today()->addDays(20)]);
        $fuera = Factura::factory()->create(['fecha_emision' => today()->subYear(), 'fecha_vencimiento' => today()->subYear()->addDays(30)]);

        $libro = $this->libro($this->actingAs($this->gerente())->get(route('reportes.exportar', [
            'tipo' => 'ventas', 'formato' => 'xlsx',
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_fin' => today()->toDateString(),
        ])));

        $numeros = array_column($libro->getSheetByName('Ventas')->toArray(), 0);
        $this->assertContains($dentro->numero_factura, $numeros);
        $this->assertNotContains($fuera->numero_factura, $numeros);
    }

    public function test_vendedor_no_accede_a_reportes(): void
    {
        $this->actingAs($this->vendedor());

        $this->get(route('reportes.index'))->assertForbidden();
        $this->get(route('reportes.exportar', ['tipo' => 'ventas', 'formato' => 'pdf', ...$this->periodo()]))->assertForbidden();
    }

    public function test_pantalla_de_reportes_muestra_vista_previa(): void
    {
        Factura::factory()->create(['fecha_emision' => today(), 'fecha_vencimiento' => today()->addDays(30)]);

        $this->actingAs($this->contador())
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Ventas del periodo')
            ->assertSee('$115', false);
    }

    private function libro(TestResponse $respuesta): Spreadsheet
    {
        $respuesta->assertOk();

        return IOFactory::load($respuesta->baseResponse->getFile()->getPathname());
    }
}
