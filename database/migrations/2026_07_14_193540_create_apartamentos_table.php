<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Apartamentos dentro de cada condominio
     */
    public function up(): void
    {
        Schema::create('apartamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->string('numero')->comment('Número o código del apartamento');
            $table->string('piso')->nullable();
            $table->decimal('metros_cuadrados', 8, 2)->nullable();
            $table->integer('habitaciones')->default(1);
            $table->integer('banos')->default(1);
            $table->boolean('ocupado')->default(false);
            $table->text('descripcion')->nullable();
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
            
            // Índice único compuesto para evitar duplicados en un condominio
            $table->unique(['condominio_id', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamentos');
    }
};