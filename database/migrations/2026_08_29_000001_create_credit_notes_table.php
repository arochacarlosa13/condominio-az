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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->foreignId('apartamento_id')
                  ->constrained('apartamentos')
                  ->cascadeOnDelete();
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->nullOnDelete();
            $table->string('numero_nota_credito', 50)->index();
            $table->date('fecha_emision');
            $table->decimal('monto_original', 12, 2)->default(0.00);
            $table->decimal('monto_original_usd', 12, 2)->default(0.00);
            $table->decimal('monto_disponible', 12, 2)->default(0.00);
            $table->decimal('monto_disponible_usd', 12, 2)->default(0.00);
            $table->decimal('tasa_cambio', 12, 2)->default(36.50);
            $table->string('estado', 30)->default('disponible'); // 'disponible', 'parcial', 'agotada', 'anulada'
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('credit_note_id')
                  ->nullable()
                  ->after('cuenta_bancaria_id')
                  ->constrained('credit_notes')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['credit_note_id']);
            $table->dropColumn('credit_note_id');
        });

        Schema::dropIfExists('credit_notes');
    }
};
