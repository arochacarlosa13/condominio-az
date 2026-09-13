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
        // 1. Ampliar tabla de condominios con jerarquía de conjuntos y torres
        Schema::table('condominios', function (Blueprint $table) {
            if (!Schema::hasColumn('condominios', 'parent_id')) {
                $table->foreignId('parent_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('condominios')
                      ->nullOnDelete()
                      ->comment('Condominio Padre si es una Torre dentro de un Conjunto Residencial');
            }
            if (!Schema::hasColumn('condominios', 'tipo_entidad')) {
                $table->enum('tipo_entidad', ['edificio_independiente', 'conjunto_residencial', 'torre_edificio'])
                      ->default('edificio_independiente')
                      ->after('nombre')
                      ->comment('Tipo de estructura arquitectónica / tenant');
            }
            if (!Schema::hasColumn('condominios', 'torre_bloque')) {
                $table->string('torre_bloque', 50)
                      ->nullable()
                      ->after('tipo_entidad')
                      ->comment('Identificador de la Torre (ej. Torre A, Torre 1, Edificio Norte)');
            }
        });

        // 2. Crear tabla pivote para asignación de administradores a múltiples condominios/torres
        if (!Schema::hasTable('condominio_user')) {
            Schema::create('condominio_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('es_principal')->default(false);
                $table->timestamps();

                $table->unique(['condominio_id', 'user_id']);
            });

            // Migrar asignaciones existentes de usuarios con condominio_id
            $users = DB::table('users')->whereNotNull('condominio_id')->get();
            foreach ($users as $u) {
                DB::table('condominio_user')->updateOrInsert(
                    ['condominio_id' => $u->condominio_id, 'user_id' => $u->id],
                    ['es_principal' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condominio_user');

        Schema::table('condominios', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'tipo_entidad', 'torre_bloque']);
        });
    }
};
