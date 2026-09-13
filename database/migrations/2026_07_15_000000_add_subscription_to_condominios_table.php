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
            $table->string('plan_suscripcion')->default('gratuito')->after('cuota_mantenimiento_base');
            $table->date('fecha_vencimiento_suscripcion')->nullable()->after('plan_suscripcion');
            $table->enum('estado_suscripcion', ['activo', 'vencido', 'suspendido'])->default('activo')->after('fecha_vencimiento_suscripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn(['plan_suscripcion', 'fecha_vencimiento_suscripcion', 'estado_suscripcion']);
        });
    }
};
