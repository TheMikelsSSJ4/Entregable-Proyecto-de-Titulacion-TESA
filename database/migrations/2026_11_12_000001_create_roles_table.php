<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DER · ROLES (id int PK, nombre varchar).
 * Catálogo de perfiles: Gerente, Contador, Vendedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
