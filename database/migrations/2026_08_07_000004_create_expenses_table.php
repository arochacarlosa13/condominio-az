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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('descripcion');
            $table->decimal('monto_bs', 12, 2)->default(0);
            $table->decimal('monto_usd', 10, 2)->default(0);
            $table->decimal('tasa_cambio', 10, 2)->default(36.50);
            $table->string('categoria')->default('mantenimiento'); // 'servicios', 'mantenimiento', 'sueldos', 'suministros', 'otros'
            $table->date('fecha_gasto');
            $table->enum('estado_pago', ['pendiente', 'pagado'])->default('pagado');
            $table->string('proveedor')->nullable();
            $table->string('referencia_pago')->nullable();
            $table->string('comprobante_path')->nullable();
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
        Schema::dropIfExists('expenses');
    }
};
