<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bancos = [
            ['nombre' => 'Banco de Venezuela', 'codigo' => '0102', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banesco', 'codigo' => '0134', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Mercantil', 'codigo' => '0105', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'BBVA Provincial', 'codigo' => '0108', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Nacional de Crédito BNC', 'codigo' => '0191', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Bancamiga', 'codigo' => '0172', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banplus', 'codigo' => '0174', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco del Tesoro', 'codigo' => '0163', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Exterior', 'codigo' => '0115', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Caroní', 'codigo' => '0128', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Plaza', 'codigo' => '0138', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Banco Activo', 'codigo' => '0171', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => '100% Banco', 'codigo' => '0156', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mi Banco', 'codigo' => '0169', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Sofitasa', 'codigo' => '0137', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('bancos')->insert($bancos);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('bancos')->truncate();
    }
};
