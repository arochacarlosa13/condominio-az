<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla principal para el multi-tenant
     */
    public function up(): void
    {
        Schema::create('condominios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('direccion');
            $table->string('rif')->unique()->comment('Registro de Información Fiscal');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->integer('numero_apartamentos')->default(0);
            $table->decimal('cuota_mantenimiento_base', 10, 2)->default(0)
                  ->comment('Cuota base mensual en Bs');
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
        Schema::dropIfExists('condominios');
    }
};