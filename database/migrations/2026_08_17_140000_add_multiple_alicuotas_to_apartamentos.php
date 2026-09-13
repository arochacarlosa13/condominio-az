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
        Schema::table('apartamentos', function (Blueprint $table) {
            if (!Schema::hasColumn('apartamentos', 'alicuota_2')) {
                $table->decimal('alicuota_2', 12, 8)->default(0.00000000)->after('alicuota')->comment('Alícuota 2 - Torre o Sector específico (ej. 2.50000000)');
            }
            if (!Schema::hasColumn('apartamentos', 'alicuota_3')) {
                $table->decimal('alicuota_3', 12, 8)->default(0.00000000)->after('alicuota_2')->comment('Alícuota 3 - Estacionamiento / Maletero / Especial (ej. 0.85000000)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn(['alicuota_2', 'alicuota_3']);
        });
    }
};
