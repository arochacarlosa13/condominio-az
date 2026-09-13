<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Comisiones por pagos (ej. 3% del monto)
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->cascadeOnDelete();
            $table->decimal('porcentaje', 5, 2)
                  ->comment('Porcentaje de comisión');
            $table->decimal('monto', 10, 2)
                  ->comment('Monto de la comisión');
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};