<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para almacenar N cuentas bancarias y canales de Pago Móvil por condominio.
     */
    public function up(): void
    {
        Schema::create('condominio_cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')
                  ->constrained('condominios')
                  ->cascadeOnDelete();
            $table->string('banco_nombre');
            $table->string('tipo_cuenta')->default('corriente'); // corriente, ahorro, custodia_usd, pago_movil, zelle, efectivo
            $table->string('moneda')->default('VES'); // VES, USD, EUR
            $table->string('numero_cuenta')->nullable();
            $table->string('titular_nombre')->nullable();
            $table->string('titular_identificacion')->nullable(); // RIF o Cédula
            $table->string('telefono_pago_movil')->nullable();
            $table->boolean('es_pago_movil')->default(false);
            $table->text('instrucciones')->nullable();
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
        Schema::dropIfExists('condominio_cuentas_bancarias');
    }
};
