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
        // 1. Agregar columna plan_id
        Schema::table('condominios', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('estado_suscripcion')->constrained('planes')->onDelete('set null');
        });

        // 2. Asociar planes existentes basados en el string de plan_suscripcion
        $condominios = DB::table('condominios')->get();
        $planes = DB::table('planes')->get();

        foreach ($condominios as $condo) {
            $planNombre = strtolower($condo->plan_suscripcion);
            // Si el valor actual está vacío o no es válido, por defecto lo ponemos en gratuito
            if (!in_array($planNombre, ['gratuito', 'basico', 'premium'])) {
                $planNombre = 'gratuito';
            }

            $plan = $planes->first(function ($p) use ($planNombre) {
                return $p->nombre === $planNombre;
            });

            if ($plan) {
                DB::table('condominios')->where('id', $condo->id)->update([
                    'plan_id' => $plan->id
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('condominios', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
