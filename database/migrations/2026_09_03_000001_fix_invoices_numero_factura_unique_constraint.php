<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Eliminar restricción de unicidad global en numero_factura si existe
            try {
                $table->dropUnique('invoices_numero_factura_unique');
            } catch (\Throwable $e) {
                // Si la convención de nombre de Laravel difiere
                try {
                    $table->dropUnique(['numero_factura']);
                } catch (\Throwable $e2) {
                    // Ignorar si no existe
                }
            }
        });

        // Asegurar la restricción única compuesta por condominio_id y numero_factura
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['condominio_id', 'numero_factura'], 'invoices_condominio_numero_factura_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            try {
                $table->dropUnique('invoices_condominio_numero_factura_unique');
            } catch (\Throwable $e) {}

            $table->unique('numero_factura', 'invoices_numero_factura_unique');
        });
    }
};
