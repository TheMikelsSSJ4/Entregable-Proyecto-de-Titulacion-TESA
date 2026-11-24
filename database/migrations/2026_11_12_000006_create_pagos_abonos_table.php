<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DER · PAGOS_ABONOS (id, factura_id FK, usuario_id FK, fecha_pago, monto_abonado,
 * metodo_pago, comprobante_referencia).
 * Relación FACTURAS 1 — N PAGOS_ABONOS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_abonos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('factura_id');
            $table->unsignedInteger('usuario_id');
            $table->date('fecha_pago');
            $table->decimal('monto_abonado', 12, 2);
            $table->string('metodo_pago', 20);
            $table->string('comprobante_referencia', 50)->nullable();
            $table->timestamps();

            $table->foreign('factura_id')->references('id')->on('facturas')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();

            $table->index('factura_id', 'pagos_abonos_factura_id_index', 'btree');
            $table->index('usuario_id', 'pagos_abonos_usuario_id_index', 'btree');
            $table->index('fecha_pago', 'pagos_abonos_fecha_pago_index', 'btree');
        });

        DB::statement('ALTER TABLE pagos_abonos ADD CONSTRAINT pagos_abonos_monto_check CHECK (monto_abonado > 0)');
        DB::statement("ALTER TABLE pagos_abonos ADD CONSTRAINT pagos_abonos_metodo_check CHECK (metodo_pago IN ('efectivo', 'transferencia', 'deposito', 'cheque', 'tarjeta'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_abonos');
    }
};
