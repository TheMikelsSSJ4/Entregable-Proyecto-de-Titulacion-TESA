<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendedor que realizó la venta o la cotización, independiente de quién
 * registró el documento (usuario_id). No borra datos: los documentos
 * existentes quedan con vendedor = usuario que los registró.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['facturas', 'cotizaciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->unsignedInteger('vendedor_id')->nullable()->after('usuario_id');
                $table->foreign('vendedor_id')->references('id')->on('usuarios')->restrictOnDelete();
                $table->index('vendedor_id', "{$tabla}_vendedor_id_index", 'btree');
            });

            DB::table($tabla)->whereNull('vendedor_id')->update(['vendedor_id' => DB::raw('usuario_id')]);

            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedInteger('vendedor_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['facturas', 'cotizaciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropForeign(['vendedor_id']);
                $table->dropIndex("{$tabla}_vendedor_id_index");
                $table->dropColumn('vendedor_id');
            });
        }
    }
};
