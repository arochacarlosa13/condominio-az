<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pagos realizados a las facturas
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->enum('metodo_pago', [
                'efectivo', 'transferencia', 'pago_movil', 'deposito', 'otro'
            ])->default('transferencia');
            $table->string('referencia')->nullable()
                  ->comment('Número de referencia bancaria');
            $table->string('banco')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};