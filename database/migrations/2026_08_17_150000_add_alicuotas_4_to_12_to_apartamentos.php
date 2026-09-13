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
            for ($i = 4; $i <= 12; $i++) {
                $column = "alicuota_{$i}";
                if (!Schema::hasColumn('apartamentos', $column)) {
                    $table->decimal($column, 12, 8)->default(0.00000000)->after("alicuota_" . ($i - 1))->comment("Alícuota {$i} configurable");
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $columns = [];
            for ($i = 4; $i <= 12; $i++) {
                $columns[] = "alicuota_{$i}";
            }
            $table->dropColumn($columns);
        });
    }
};
