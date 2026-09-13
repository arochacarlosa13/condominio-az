<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'numero_recibo_pago')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('numero_recibo_pago', 50)->nullable()->after('estado')->index();
            });
        }

        // Asignación retroactiva de correlativos RP2026-00001 a pagos aprobados
        try {
            $condominios = DB::table('condominios')->pluck('id');
            foreach ($condominios as $condoId) {
                $approvedPayments = DB::table('payments')
                    ->where('condominio_id', $condoId)
                    ->where('estado', 'aprobado')
                    ->whereNull('deleted_at')
                    ->orderBy('fecha_pago', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($approvedPayments->isEmpty()) {
                    continue;
                }

                // Agrupar en PHP por año de fecha_pago
                $byYear = $approvedPayments->groupBy(function ($p) {
                    return $p->fecha_pago ? Carbon::parse($p->fecha_pago)->year : date('Y');
                });

                foreach ($byYear as $year => $yearPayments) {
                    $seqRp = 1;
                    foreach ($yearPayments as $pago) {
                        $numRp = sprintf("RP%d-%05d", $year, $seqRp);
                        DB::table('payments')
                            ->where('id', $pago->id)
                            ->update(['numero_recibo_pago' => $numRp]);
                        $seqRp++;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorar si no hay datos
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payments', 'numero_recibo_pago')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('numero_recibo_pago');
            });
        }
    }
};
