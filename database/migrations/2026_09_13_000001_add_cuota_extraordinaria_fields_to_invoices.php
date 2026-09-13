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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'tipo_recibo')) {
                $table->string('tipo_recibo', 50)->default('ordinario')->after('periodo')->index();
            }
            if (!Schema::hasColumn('invoices', 'titulo_proyecto')) {
                $table->string('titulo_proyecto', 255)->nullable()->after('tipo_recibo');
            }
            if (!Schema::hasColumn('invoices', 'modalidad_calculo')) {
                $table->string('modalidad_calculo', 50)->default('alicuota')->after('titulo_proyecto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tipo_recibo', 'titulo_proyecto', 'modalidad_calculo']);
        });
    }
};
