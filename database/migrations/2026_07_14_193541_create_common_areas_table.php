<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Áreas comunes del condominio
     */
    public function up(): void
    {
        Schema::create('common_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('ubicacion')->nullable();
            $table->integer('capacidad_maxima')->nullable();
            $table->boolean('requiere_reserva')->default(true);
            $table->decimal('costo_reserva', 10, 2)->default(0)
                  ->comment('Costo por uso, 0 si es gratuito');
            $table->json('horarios_disponibles')->nullable()
                  ->comment('Horarios en formato JSON');
            $table->json('reglas_uso')->nullable()
                  ->comment('Reglas en formato JSON');
            $table->boolean('activo')->default(true);
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('common_areas');
    }
};