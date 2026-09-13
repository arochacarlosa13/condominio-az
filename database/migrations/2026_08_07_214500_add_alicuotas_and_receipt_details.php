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
        // 1. Ampliar tabla de apartamentos con alícuota de alta precisión
        Schema::table('apartamentos', function (Blueprint $table) {
            if (!Schema::hasColumn('apartamentos', 'alicuota')) {
                $table->decimal('alicuota', 12, 8)->default(0.00000000)->after('metros_cuadrados')->comment('Porcentaje de alícuota (ej. 3.03460000)');
            }
            if (!Schema::hasColumn('apartamentos', 'grupo_alicuota')) {
                $table->string('grupo_alicuota', 10)->default('1')->after('alicuota')->comment('Identificador de torre o alícuota (ej. 1, 2, 3)');
            }
        });

        // 2. Ampliar tabla de condominios con datos bancarios, notas legales y fondos de reserva
        Schema::table('condominios', function (Blueprint $table) {
            if (!Schema::hasColumn('condominios', 'cuenta_bancaria_bs')) {
                $table->string('cuenta_bancaria_bs')->nullable()->after('tasa_cambio')->comment('Cuenta corriente en Bs');
            }
            if (!Schema::hasColumn('condominios', 'cuenta_bancaria_usd')) {
                $table->string('cuenta_bancaria_usd')->nullable()->after('cuenta_bancaria_bs')->comment('Cuenta en divisas');
            }
            if (!Schema::hasColumn('condominios', 'banco_nombre')) {
                $table->string('banco_nombre')->nullable()->default('BNC')->after('cuenta_bancaria_usd');
            }
            if (!Schema::hasColumn('condominios', 'fondo_reserva_porcentaje')) {
                $table->decimal('fondo_reserva_porcentaje', 5, 2)->default(10.00)->after('banco_nombre');
            }
            if (!Schema::hasColumn('condominios', 'fondo_reserva_acumulado')) {
                $table->decimal('fondo_reserva_acumulado', 12, 2)->default(2997.06)->after('fondo_reserva_porcentaje');
            }
            if (!Schema::hasColumn('condominios', 'notas_recibo')) {
                $table->text('notas_recibo')->nullable()->after('fondo_reserva_acumulado');
            }
        });

        // 3. Ampliar tabla de invoices para almacenar el desglose de gastos y fondos
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'detalles_gastos')) {
                $table->json('detalles_gastos')->nullable()->after('descripcion')->comment('Array de conceptos, montos y alícuotas');
            }
            if (!Schema::hasColumn('invoices', 'fondos')) {
                $table->json('fondos')->nullable()->after('detalles_gastos')->comment('Array de fondos de reserva y montos');
            }
            if (!Schema::hasColumn('invoices', 'monto_total_usd')) {
                $table->decimal('monto_total_usd', 12, 2)->default(0.00)->after('monto_total');
            }
            if (!Schema::hasColumn('invoices', 'monto_alicuota_usd')) {
                $table->decimal('monto_alicuota_usd', 12, 2)->default(0.00)->after('monto_total_usd');
            }
            if (!Schema::hasColumn('invoices', 'tasa_cambio')) {
                $table->decimal('tasa_cambio', 12, 2)->default(36.50)->after('monto_alicuota_usd');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn(['alicuota', 'grupo_alicuota']);
        });
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn(['cuenta_bancaria_bs', 'cuenta_bancaria_usd', 'banco_nombre', 'fondo_reserva_porcentaje', 'fondo_reserva_acumulado', 'notas_recibo']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['detalles_gastos', 'fondos', 'monto_total_usd', 'monto_alicuota_usd', 'tasa_cambio']);
        });
    }
};
