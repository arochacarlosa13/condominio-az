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
        // Drop Postgres check constraint if exists
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_metodo_pago_check;');
        }

        // Change column to string to allow all modern payment methods (efectivo_usd, efectivo_ves, zelle, pago_movil, etc.)
        Schema::table('payments', function (Blueprint $table) {
            $table->string('metodo_pago', 50)->default('transferencia')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('metodo_pago', 50)->default('transferencia')->change();
        });
    }
};
