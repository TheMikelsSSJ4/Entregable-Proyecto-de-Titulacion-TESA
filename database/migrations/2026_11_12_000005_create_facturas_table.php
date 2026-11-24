<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DER · FACTURAS (id, numero_factura, cliente_id FK, usuario_id FK, tipo_transaccion,
 * fecha_emision, fecha_vencimiento, subtotal, iva, total, estado).
 * Ampliada con descuento y cotizacion_id (relación COTIZACIONES — FACTURAS del DER);
 * subtotal, descuento, IVA y total se calculan a partir de factura_detalles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('numero_factura', 17)->unique();
            $table->unsignedInteger('cliente_id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('cotizacion_id')->nullable();
            $table->string('tipo_transaccion', 10);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->restrictOnDelete();

            $table->index('cliente_id', 'facturas_cliente_id_index', 'btree');
            $table->index('usuario_id', 'facturas_usuario_id_index', 'btree');
            $table->index('cotizacion_id', 'facturas_cotizacion_id_index', 'btree');
            $table->index('fecha_emision', 'facturas_fecha_emision_index', 'btree');
            // Consulta principal de Cartera: facturas abiertas ordenadas por vencimiento.
            $table->index(['estado', 'fecha_vencimiento'], 'facturas_estado_vencimiento_index', 'btree');
        });

        DB::statement("ALTER TABLE facturas ADD CONSTRAINT facturas_numero_formato_check CHECK (numero_factura ~ '^[0-9]{3}-[0-9]{3}-[0-9]{9}$')");
        DB::statement("ALTER TABLE facturas ADD CONSTRAINT facturas_tipo_check CHECK (tipo_transaccion IN ('contado', 'credito'))");
        DB::statement("ALTER TABLE facturas ADD CONSTRAINT facturas_estado_check CHECK (estado IN ('pendiente', 'parcial', 'pagada', 'anulada'))");
        DB::statement('ALTER TABLE facturas ADD CONSTRAINT facturas_montos_check CHECK (subtotal >= 0 AND descuento >= 0 AND iva >= 0 AND total = subtotal + iva)');
        DB::statement('ALTER TABLE facturas ADD CONSTRAINT facturas_fechas_check CHECK (fecha_vencimiento >= fecha_emision)');
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
