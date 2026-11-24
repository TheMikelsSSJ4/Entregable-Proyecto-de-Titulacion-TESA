<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DER · USUARIOS (id, rol_id FK, nombre_completo, email, password, estado).
 * Relación ROLES 1 — N USUARIOS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rol_id');
            $table->string('nombre_completo', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();

            $table->foreign('rol_id')->references('id')->on('roles')->restrictOnDelete();

            $table->index('rol_id', 'usuarios_rol_id_index', 'btree');
            $table->index('estado', 'usuarios_estado_index', 'btree');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_estado_check CHECK (estado IN ('activo', 'inactivo'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
