<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DER · COTIZACIONES (id, cliente_id FK, usuario_id FK, fecha_emision, total, estado).
 * Ampliada con subtotal, descuento e IVA: los valores se calculan a partir de
 * cotizacion_detalles (maestro-detalle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id');
            $table->unsignedInteger('usuario_id');
            $table->date('fecha_emision');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();

            $table->index('cliente_id', 'cotizaciones_cliente_id_index', 'btree');
            $table->index('usuario_id', 'cotizaciones_usuario_id_index', 'btree');
            $table->index(['estado', 'fecha_emision'], 'cotizaciones_estado_fecha_index', 'btree');
        });

        DB::statement('ALTER TABLE cotizaciones ADD CONSTRAINT cotizaciones_montos_check CHECK (subtotal >= 0 AND descuento >= 0 AND iva >= 0 AND total = subtotal + iva)');
        DB::statement("ALTER TABLE cotizaciones ADD CONSTRAINT cotizaciones_estado_check CHECK (estado IN ('pendiente', 'aprobada', 'rechazada', 'facturada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
