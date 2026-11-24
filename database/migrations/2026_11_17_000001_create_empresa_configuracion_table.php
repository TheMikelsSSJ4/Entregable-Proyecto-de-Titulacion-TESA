<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de la empresa emisora que se imprimen en PDFs y Excels.
 * Tabla de un único registro: el CHECK (id = 1) impide crear un segundo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('razon_social', 200);
            $table->string('ruc', 13);
            $table->text('direccion');
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE empresa_configuracion ADD CONSTRAINT empresa_configuracion_unico_check CHECK (id = 1)');
        DB::statement("ALTER TABLE empresa_configuracion ADD CONSTRAINT empresa_configuracion_ruc_check CHECK (ruc ~ '^[0-9]{13}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_configuracion');
    }
};
