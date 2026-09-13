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
        if (!Schema::hasColumn('invoices', 'numero_recibo_general')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('numero_recibo_general', 50)->nullable()->after('numero_factura')->index();
            });
        }

        // Migración de datos existentes a la nueva nomenclatura de correlativos (cross-database PHP)
        try {
            $condominios = DB::table('condominios')->pluck('id');
            foreach ($condominios as $condoId) {
                $allInvoices = DB::table('invoices')
                    ->where('condominio_id', $condoId)
                    ->whereNull('deleted_at')
                    ->orderBy('fecha_emision', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($allInvoices->isEmpty()) {
                    continue;
                }

                // Agrupar en PHP por año de fecha_emision
                $byYear = $allInvoices->groupBy(function ($inv) {
                    return $inv->fecha_emision ? Carbon::parse($inv->fecha_emision)->year : date('Y');
                });

                foreach ($byYear as $year => $yearInvoices) {
                    // Agrupar dentro del año por período
                    $byPeriod = $yearInvoices->groupBy('periodo');
                    $seqRg = 1;
                    $seqRi = 1;

                    foreach ($byPeriod as $periodo => $periodInvoices) {
                        $numRg = sprintf("RG%d-%05d", $year, $seqRg);

                        foreach ($periodInvoices as $inv) {
                            $numRi = sprintf("RI%d-%d-%05d", $year, $seqRg, $seqRi);

                            DB::table('invoices')
                                ->where('id', $inv->id)
                                ->update([
                                    'numero_recibo_general' => $numRg,
                                    'numero_factura' => $numRi,
                                ]);

                            $seqRi++;
                        }

                        $seqRg++;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log/Ignorar si la tabla está vacía
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'numero_recibo_general')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('numero_recibo_general');
            });
        }
    }
};
