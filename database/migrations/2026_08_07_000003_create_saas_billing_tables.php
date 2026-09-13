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
        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->string('numero_factura')->unique();
            $table->string('periodo'); // YYYYMM
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_total_usd', 10, 2)->default(0);
            $table->decimal('monto_total_bs', 12, 2)->default(0);
            $table->decimal('tasa_cambio', 10, 2)->default(36.50);
            $table->decimal('monto_pagado_usd', 10, 2)->default(0);
            $table->decimal('monto_pagado_bs', 12, 2)->default(0);
            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'anulado'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('saas_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_invoice_id')->nullable()->constrained('saas_invoices')->nullOnDelete();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->date('fecha_pago');
            $table->string('metodo_pago')->default('transferencia');
            $table->string('referencia')->nullable();
            $table->string('banco')->nullable();
            $table->decimal('monto_usd', 10, 2)->default(0);
            $table->decimal('monto_bs', 12, 2)->default(0);
            $table->decimal('tasa_cambio', 10, 2)->default(36.50);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('aprobado');
            $table->string('comprobante_path')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_payments');
        Schema::dropIfExists('saas_invoices');
    }
};
