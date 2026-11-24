<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Líneas de una cotización. Precio, descuento e IVA se copian del producto al
 * momento de cotizar, para que cambios posteriores del catálogo no alteren el documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cotizacion_id');
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

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->restrictOnDelete();
            $table->foreign('producto_id')->references('id')->on('productos')->restrictOnDelete();

            $table->index('cotizacion_id', 'cotizacion_detalles_cotizacion_id_index', 'btree');
            $table->index('producto_id', 'cotizacion_detalles_producto_id_index', 'btree');
        });

        DB::statement('ALTER TABLE cotizacion_detalles ADD CONSTRAINT cotizacion_detalles_montos_check CHECK (cantidad > 0 AND precio_unitario >= 0 AND descuento >= 0 AND subtotal >= 0 AND iva >= 0 AND total = subtotal + iva)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_detalles');
    }
};
