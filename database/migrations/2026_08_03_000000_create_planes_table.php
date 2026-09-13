<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('costo_base', 10, 2)->default(0.00);
            $table->decimal('costo_por_apartamento', 10, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Siembra inicial de los planes de la plataforma
        DB::table('planes')->insert([
            [
                'nombre' => 'gratuito',
                'descripcion' => 'Plan de prueba gratuito sin costo mensual.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 0.00,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'basico',
                'descripcion' => 'Plan estándar con cobro mensual de $0.50 por apartamento.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 0.50,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'premium',
                'descripcion' => 'Plan completo con soporte prioritario y cobro de $1.00 por apartamento.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 1.00,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
