<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DER · CLIENTES (id, ruc_cedula, razon_social, direccion, telefono, correo).
 * Cédula (10 dígitos) o RUC (13 dígitos) según el Reglamento de Comprobantes del SRI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ruc_cedula', 13)->unique();
            $table->string('razon_social', 200);
            $table->text('direccion');
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->nullable();
            $table->timestamps();

            $table->index('razon_social', 'clientes_razon_social_index', 'btree');
        });

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_ruc_cedula_check CHECK (ruc_cedula ~ '^[0-9]{10}([0-9]{3})?$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
