<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Relación entre apartamentos y propietarios
     */
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')
                  ->constrained('apartamentos')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Usuario asociado si tiene acceso al sistema');
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->string('nombre_completo');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->boolean('es_propietario_principal')->default(false);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
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
        Schema::dropIfExists('propietarios');
    }
};