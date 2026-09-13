<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            if (!Schema::hasColumn('condominios', 'pago_movil_banco')) {
                $table->string('pago_movil_banco')->nullable()->after('cuenta_bancaria_usd');
            }
            if (!Schema::hasColumn('condominios', 'pago_movil_cedula')) {
                $table->string('pago_movil_cedula')->nullable()->after('pago_movil_banco');
            }
            if (!Schema::hasColumn('condominios', 'pago_movil_telefono')) {
                $table->string('pago_movil_telefono')->nullable()->after('pago_movil_cedula');
            }
        });
    }

    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropColumn(['pago_movil_banco', 'pago_movil_cedula', 'pago_movil_telefono']);
        });
    }
};
