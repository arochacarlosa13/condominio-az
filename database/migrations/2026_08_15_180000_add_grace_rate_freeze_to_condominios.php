<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            if (!Schema::hasColumn('condominios', 'mantener_tasa_emision_5_dias')) {
                $table->boolean('mantener_tasa_emision_5_dias')
                      ->default(true)
                      ->after('tasa_cambio')
                      ->comment('Mantiene la tasa de emisión durante los primeros 5 días. Al 6to día en adelante toma la tasa del día.');
            }
            if (!Schema::hasColumn('condominios', 'dias_congelar_tasa')) {
                $table->integer('dias_congelar_tasa')
                      ->default(5)
                      ->after('mantener_tasa_emision_5_dias')
                      ->comment('Número de días que se mantendrá fija la tasa de emisión');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn(['mantener_tasa_emision_5_dias', 'dias_congelar_tasa']);
        });
    }
};
