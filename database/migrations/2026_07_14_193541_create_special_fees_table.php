<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cuotas especiales o extraordinarias
     */
    public function up(): void
    {
        Schema::create('special_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion');
            $table->decimal('monto_total', 10, 2);
            $table->decimal('monto_por_apartamento', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['activa', 'finalizada', 'cancelada'])
                  ->default('activa');
            $table->string('periodo');
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_fees');
    }
};