<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Votos de las encuestas
     */
    public function up(): void
    {
        // Primero necesitamos la tabla de opciones
        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')
                  ->constrained('polls')
                  ->cascadeOnDelete();
            $table->string('texto');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Tabla de votos
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')
                  ->constrained('polls')
                  ->cascadeOnDelete();
            $table->foreignId('poll_option_id')
                  ->constrained('poll_options')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('apartamento_id')
                  ->constrained('apartamentos')
                  ->cascadeOnDelete();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->timestamps();
            
            // Un usuario solo puede votar una vez por encuesta (si no permite múltiple)
            $table->unique(['poll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('poll_options');
    }
};