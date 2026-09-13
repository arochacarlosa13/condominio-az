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
        Schema::create('notificacion_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->nullable()->constrained('condominios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['whatsapp', 'email'])->default('email');
            $table->string('plantilla')->default('general'); // 'recordatorio_pago', 'confirmacion_pago', 'reserva', 'comunicado', 'bienvenida'
            $table->string('destinatario');
            $table->text('mensaje');
            $table->enum('estado', ['enviado', 'fallido'])->default('enviado');
            $table->text('error_mensaje')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacion_historial');
    }
};
