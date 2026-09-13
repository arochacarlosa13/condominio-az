<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Traza de auditoría para tracking de cambios
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('condominio_id')
                  ->nullable()
                  ->constrained('condominios')
                  ->nullOnDelete();
            $table->string('accion')->comment('create, update, delete, login, logout');
            $table->string('modelo')->comment('Modelo afectado');
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->json('datos_anteriores')->nullable()
                  ->comment('Datos antes del cambio');
            $table->json('datos_nuevos')->nullable()
                  ->comment('Datos después del cambio');
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index(['modelo', 'modelo_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['condominio_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};