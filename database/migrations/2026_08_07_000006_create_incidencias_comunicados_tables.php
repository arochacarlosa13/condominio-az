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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->foreignId('apartamento_id')->nullable()->constrained('apartamentos')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            $table->string('responsable_nombre')->nullable();
            $table->date('fecha_solucion')->nullable();
            $table->enum('estado', ['reportado', 'en_proceso', 'resuelto'])->default('reportado');
            $table->string('foto_adjunta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('contenido');
            $table->date('fecha_publicacion');
            $table->date('fecha_expiracion')->nullable();
            $table->string('archivo_path')->nullable();
            $table->boolean('enviar_email')->default(false);
            $table->boolean('enviar_whatsapp')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunicados');
        Schema::dropIfExists('incidencias');
    }
};
