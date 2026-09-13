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
            $table->decimal('tasa_cambio', 10, 2)->default(36.50)->after('cuota_mantenimiento_base')
                  ->comment('Tasa de cambio del día (Bs por USD)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn('tasa_cambio');
        });
    }
};
