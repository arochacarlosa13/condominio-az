<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Usuarios del sistema con roles multi-tenant
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('rol')->default('propietario')->comment('Roles del sistema: master, admin, propietario, etc.');
            $table->foreignId('condominio_id')
                  ->nullable()
                  ->constrained('condominios')
                  ->nullOnDelete()
                  ->comment('Null para master, FK para admin y propietario');
            $table->string('telefono')->nullable();
            $table->string('cedula')->nullable()->comment('Cédula de identidad');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->softDeletes()->comment('Eliminación lógica');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};