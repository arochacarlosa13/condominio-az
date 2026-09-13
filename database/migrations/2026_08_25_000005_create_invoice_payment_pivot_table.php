<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote para asociar una sola transacción de pago a múltiples facturas/recibos de cobro.
     */
    public function up(): void
    {
        Schema::create('invoice_payment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')
                  ->constrained('payments')
                  ->cascadeOnDelete();
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();
            $table->decimal('monto_aplicado', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cuenta_bancaria_id')
                  ->nullable()
                  ->constrained('condominio_cuentas_bancarias')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cuenta_bancaria_id']);
            $table->dropColumn('cuenta_bancaria_id');
        });

        Schema::dropIfExists('invoice_payment');
    }
};
