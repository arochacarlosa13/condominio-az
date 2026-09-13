<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reservas de áreas comunes
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('common_area_id')
                  ->constrained('common_areas')
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
            $table->date('fecha_reserva');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])
                  ->default('pendiente');
            $table->text('motivo')->nullable();
            $table->integer('numero_personas')->default(1);
            $table->decimal('costo_total', 10, 2)->default(0);
            $table->boolean('pago_realizado')->default(false);
            $table->text('notas')->nullable();
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};