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
        Schema::create('condominio_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('ali', 10)->default('1')->comment('Grupo de alícuota: 1=General, 2=Torre A, 3=Torre B');
            $table->string('concepto')->comment('Descripción o concepto del gasto (ej. Sueldos y Salarios)');
            $table->decimal('monto_base', 12, 2)->default(0.00)->comment('Monto en USD del gasto para todo el edificio');
            $table->string('tipo')->default('fijo')->comment('fijo, variable, extraordinario');
            $table->string('categoria')->default('mantenimiento')->comment('servicios, mantenimiento, sueldos, administracion');
            $table->date('vigente_desde')->nullable()->comment('Fecha o mes desde el cual rige este monto');
            $table->date('vigente_hasta')->nullable()->comment('Fecha en que se sustituye');
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condominio_conceptos');
    }
};
