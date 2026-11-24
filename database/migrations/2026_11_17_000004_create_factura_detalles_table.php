<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Líneas de una factura con precio, descuento e IVA congelados al momento de la venta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('factura_id');
            $table->unsignedInteger('producto_id');
            $table->string('descripcion', 200);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->decimal('descuento', 12, 2);
            $table->decimal('porcentaje_iva', 5, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->foreign('factura_id')->references('id')->on('facturas')->restrictOnDelete();
            $table->foreign('producto_id')->references('id')->on('productos')->restrictOnDelete();

            $table->index('factura_id', 'factura_detalles_factura_id_index', 'btree');
            $table->index('producto_id', 'factura_detalles_producto_id_index', 'btree');
        });

        DB::statement('ALTER TABLE factura_detalles ADD CONSTRAINT factura_detalles_montos_check CHECK (cantidad > 0 AND precio_unitario >= 0 AND descuento >= 0 AND subtotal >= 0 AND iva >= 0 AND total = subtotal + iva)');
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
