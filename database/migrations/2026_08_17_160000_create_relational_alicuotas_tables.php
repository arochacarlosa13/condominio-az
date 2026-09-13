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
        // 1. Tabla de catálogo de alícuotas por condominio
        Schema::create('condominio_alicuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->integer('numero')->default(1)->comment('Número correlativo de alícuota en el condominio (1, 2, 3...)');
            $table->string('nombre', 100)->comment('Nombre descriptivo (ej. Gastos Generales, Torre A, Estacionamiento)');
            $table->string('descripcion', 255)->nullable()->comment('Detalle adicional o notas');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['condominio_id', 'numero']);
        });

        // 2. Tabla pivote / asignación de alícuotas a cada apartamento
        Schema::create('apartamento_alicuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained('apartamentos')->cascadeOnDelete();
            $table->foreignId('condominio_alicuota_id')->constrained('condominio_alicuotas')->cascadeOnDelete();
            $table->decimal('porcentaje', 12, 8)->default(0.00000000)->comment('Porcentaje asignado a esta unidad');
            $table->timestamps();

            $table->unique(['apartamento_id', 'condominio_alicuota_id']);
        });

        // 3. Migración de datos existentes: Crear Ali 1 por defecto para condominios existentes
        $condos = DB::table('condominios')->get();
        foreach ($condos as $condo) {
            $ali1Id = DB::table('condominio_alicuotas')->insertGetId([
                'condominio_id' => $condo->id,
                'numero' => 1,
                'nombre' => 'Gastos Generales',
                'descripcion' => 'Alícuota principal de gastos comunes',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Migrar alícuotas de apartamentos existentes en este condominio
            $aptos = DB::table('apartamentos')->where('condominio_id', $condo->id)->get();
            foreach ($aptos as $apto) {
                $pct1 = (float)($apto->alicuota ?? 0);
                if ($pct1 > 0) {
                    DB::table('apartamento_alicuotas')->insertOrIgnore([
                        'apartamento_id' => $apto->id,
                        'condominio_alicuota_id' => $ali1Id,
                        'porcentaje' => $pct1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Migrar alícuotas 2 a 12 si existen columnas y valores
                for ($i = 2; $i <= 12; $i++) {
                    $col = "alicuota_{$i}";
                    if (isset($apto->$col) && (float)$apto->$col > 0) {
                        // Verificar o crear definición de alícuota en condominio_alicuotas
                        $aliDef = DB::table('condominio_alicuotas')
                            ->where('condominio_id', $condo->id)
                            ->where('numero', $i)
                            ->first();

                        if (!$aliDef) {
                            $nombreDef = match ($i) {
                                2 => 'Torre / Sector',
                                3 => 'Estacionamiento',
                                4 => 'Maletero / Depósito',
                                5 => 'Locales Comerciales',
                                6 => 'Áreas Club / Recreación',
                                default => "Alícuota Especial {$i}",
                            };

                            $aliDefId = DB::table('condominio_alicuotas')->insertGetId([
                                'condominio_id' => $condo->id,
                                'numero' => $i,
                                'nombre' => $nombreDef,
                                'descripcion' => "Alícuota #{$i} del inmueble",
                                'activo' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $aliDefId = $aliDef->id;
                        }

                        DB::table('apartamento_alicuotas')->insertOrIgnore([
                            'apartamento_id' => $apto->id,
                            'condominio_alicuota_id' => $aliDefId,
                            'porcentaje' => (float)$apto->$col,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_alicuotas');
        Schema::dropIfExists('condominio_alicuotas');
    }
};
