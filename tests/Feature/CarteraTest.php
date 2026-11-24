<?php

namespace Tests\Feature;

use App\Enums\EstadoFactura;
use App\Models\Factura;
use App\Models\PagoAbono;
use App\Services\CarteraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteraTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosAbono(Factura $factura, array $cambios = []): array
    {
        return array_merge([
            'factura_id' => $factura->id,
            'fecha_pago' => today()->toDateString(),
            'monto_abonado' => '40.00',
            'metodo_pago' => 'efectivo',
        ], $cambios);
    }

    public function test_abono_parcial_deja_la_factura_en_estado_parcial(): void
    {
        $factura = Factura::factory()->create();

        $this->actingAs($this->contador())
            ->post(route('abonos.store'), $this->datosAbono($factura))
            ->assertRedirect(route('facturas.show', $factura));

        $factura->refresh();
        $this->assertSame(EstadoFactura::Parcial, $factura->estado);
        $this->assertSame('75.00', $factura->saldo());
    }

    public function test_abono_por_el_saldo_total_marca_la_factura_como_pagada(): void
    {
        $factura = Factura::factory()->create();
        $this->actingAs($this->contador());

        $this->post(route('abonos.store'), $this->datosAbono($factura, ['monto_abonado' => '15.00']));
        $this->post(route('abonos.store'), $this->datosAbono($factura, ['monto_abonado' => '100.00']));

        $factura->refresh();
        $this->assertSame(EstadoFactura::Pagada, $factura->estado);
        $this->assertSame('0.00', $factura->saldo());
        $this->assertSame(0, $factura->diasMora());
    }

    public function test_rechaza_abono_mayor_al_saldo(): void
    {
        $factura = Factura::factory()->create();

        $this->actingAs($this->contador())
            ->post(route('abonos.store'), $this->datosAbono($factura, ['monto_abonado' => '115.01']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pagos_abonos', 0);
        $this->assertSame(EstadoFactura::Pendiente, $factura->fresh()->estado);
    }

    public function test_rechaza_abono_a_factura_pagada_o_anulada(): void
    {
        $anulada = Factura::factory()->create(['estado' => EstadoFactura::Anulada]);

        $this->actingAs($this->contador())
            ->post(route('abonos.store'), $this->datosAbono($anulada))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pagos_abonos', 0);
    }

    public function test_exige_comprobante_cuando_el_pago_no_es_en_efectivo(): void
    {
        $factura = Factura::factory()->create();

        $this->actingAs($this->contador())
            ->post(route('abonos.store'), $this->datosAbono($factura, ['metodo_pago' => 'transferencia']))
            ->assertSessionHasErrors('comprobante_referencia');
    }

    public function test_gerente_revierte_abono_y_se_recalcula_el_estado(): void
    {
        $factura = Factura::factory()->create();
        $pago = app(CarteraService::class)->registrarAbono($factura, [
            'fecha_pago' => today()->toDateString(),
            'monto_abonado' => '115.00',
            'metodo_pago' => 'efectivo',
        ], $this->contador());
        $this->assertSame(EstadoFactura::Pagada, $factura->fresh()->estado);

        $this->actingAs($this->gerente())
            ->delete(route('abonos.destroy', $pago))
            ->assertRedirect(route('facturas.show', $factura));

        $this->assertModelMissing($pago);
        $this->assertSame(EstadoFactura::Pendiente, $factura->fresh()->estado);
        $this->assertSame('115.00', $factura->fresh()->saldo());
    }

    public function test_calcula_dias_de_mora_y_tramos_de_antiguedad(): void
    {
        $vencida18 = Factura::factory()->vencida(18)->create();
        $vencida95 = Factura::factory()->vencida(95)->create();
        PagoAbono::factory()->for($vencida95)->create(['monto_abonado' => '15.00']);
        $vencida95->update(['estado' => EstadoFactura::Parcial]);
        Factura::factory()->create(['fecha_vencimiento' => today()->addDays(3)]);
        Factura::factory()->create(['estado' => EstadoFactura::Anulada]);

        $this->assertSame(18, $vencida18->diasMora());

        $resumen = app(CarteraService::class)->resumen();

        $this->assertSame('330.00', $resumen['por_cobrar']);
        $this->assertSame('215.00', $resumen['en_mora']);
        $this->assertSame(2, $resumen['facturas_vencidas']);
        $this->assertSame(1, $resumen['facturas_por_vencer']);
        $this->assertSame(3, $resumen['alertas_activas']);
        $this->assertSame('115.00', $resumen['tramos'][0]['monto']);
        $this->assertSame('100.00', $resumen['tramos'][3]['monto']);
    }

    public function test_modulo_de_cartera_filtra_facturas_vencidas(): void
    {
        $vencida = Factura::factory()->vencida(10)->create();
        $alDia = Factura::factory()->create();

        $this->actingAs($this->contador())
            ->get(route('cartera.index', ['condicion' => 'vencidas']))
            ->assertOk()
            ->assertSee($vencida->numero_factura)
            ->assertSee('10 días')
            ->assertDontSee($alDia->numero_factura);
    }
}
