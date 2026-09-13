<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->integer('max_apartamentos')->default(50)->after('costo_por_apartamento');
            $table->decimal('precio_mensual', 10, 2)->default(25.00)->after('max_apartamentos');
            $table->string('badge')->nullable()->after('precio_mensual');
            $table->boolean('destacado')->default(false)->after('badge');
            $table->json('caracteristicas')->nullable()->after('destacado');
        });

        // Actualizar o sembrar los 3 planes principales por defecto
        DB::table('planes')->truncate();

        DB::table('planes')->insert([
            [
                'id' => 1,
                'nombre' => 'Plan Básico',
                'descripcion' => 'Ideal para edificios pequeños y juntas de condominio residenciales.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 0.50,
                'max_apartamentos' => 30,
                'precio_mensual' => 15.00,
                'badge' => null,
                'destacado' => false,
                'caracteristicas' => json_encode([
                    'Hasta 30 Apartamentos',
                    'Tasa BCV diaria automatizada',
                    'Recibos en PDF con código QR',
                    'Notificaciones por correo'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'nombre' => 'Plan Profesional',
                'descripcion' => 'Para torres de mediano tamaño y administradoras profesionales.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 0.40,
                'max_apartamentos' => 120,
                'precio_mensual' => 35.00,
                'badge' => 'MÁS POPULAR',
                'destacado' => true,
                'caracteristicas' => json_encode([
                    '31 a 120 Apartamentos',
                    'Todo lo del Plan Básico',
                    'Avisos masivos por WhatsApp',
                    'Control de acceso de visitantes',
                    'Reserva de áreas comunes'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'nombre' => 'Plan Enterprise',
                'descripcion' => 'Para grandes complejos residenciales, centros comerciales y empresas administradoras.',
                'costo_base' => 0.00,
                'costo_por_apartamento' => 0.30,
                'max_apartamentos' => 999,
                'precio_mensual' => 75.00,
                'badge' => 'CORPORATIVO',
                'destacado' => false,
                'caracteristicas' => json_encode([
                    'Multi-Torres & Ilimitado',
                    'Multi-edificio y roles avanzados',
                    'Integración IoT para portones',
                    'Soporte dedicado 24/7'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['max_apartamentos', 'precio_mensual', 'badge', 'destacado', 'caracteristicas']);
        });
    }
};
