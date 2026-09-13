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
        // 1. Usuarios: intentos fallidos y bloqueo
        Schema::table('users', function (Blueprint $table) {
            $table->integer('intentos_fallidos')->default(0)->after('password');
            $table->timestamp('bloqueado_hasta')->nullable()->after('intentos_fallidos');
            $table->foreignId('apartamento_id')->nullable()->after('condominio_id')->constrained('apartamentos')->nullOnDelete();
        });

        // 2. Pagos: estado y comprobante
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('aprobado')->after('tasa_cambio');
            $table->string('comprobante_path')->nullable()->after('estado');
        });

        // 3. Condominios: porcentaje mora, días gracia, moneda base
        Schema::table('condominios', function (Blueprint $table) {
            $table->decimal('porcentaje_mora', 5, 2)->default(5.00)->after('cuota_mantenimiento_base');
            $table->integer('dias_gracia')->default(5)->after('porcentaje_mora');
            $table->string('moneda_base', 10)->default('VES')->after('dias_gracia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_mora', 'dias_gracia', 'moneda_base']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['estado', 'comprobante_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['apartamento_id']);
            $table->dropColumn(['intentos_fallidos', 'bloqueado_hasta', 'apartamento_id']);
        });
    }
};
