<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de facturación (sin control de stock ni kardex).
 * precio_final = (precio_base − descuento) + IVA, calculado por el sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 200);
            $table->decimal('precio_base', 12, 2);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->decimal('porcentaje_iva', 5, 2)->default(15);
            $table->decimal('precio_final', 12, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre', 'productos_nombre_index', 'btree');
            $table->index('activo', 'productos_activo_index', 'btree');
        });

        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_precios_check CHECK (precio_base >= 0 AND precio_final >= 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_porcentajes_check CHECK (porcentaje_descuento BETWEEN 0 AND 100 AND porcentaje_iva BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
