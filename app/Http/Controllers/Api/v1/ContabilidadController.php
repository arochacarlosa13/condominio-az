<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Condominio;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ContabilidadController extends BaseController
{
    /**
     * Libro Mayor consolidado (Ingresos y Egresos cronológicos).
     */
    public function libroMayor(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();

        $ingresos = Payment::where('condominio_id', $condominioId)
            ->where('estado', 'aprobado')
            ->select('id', 'fecha_pago as fecha', 'monto as monto_bs', 'metodo_pago as concepto', 'referencia', 'created_at')
            ->get()
            ->map(function ($item) {
                $item->tipo = 'ingreso';
                return $item;
            });

        $egresos = Expense::where('condominio_id', $condominioId)
            ->where('estado_pago', 'pagado')
            ->select('id', 'fecha_gasto as fecha', 'monto_bs', 'descripcion as concepto', 'referencia_pago as referencia', 'created_at')
            ->get()
            ->map(function ($item) {
                $item->tipo = 'egreso';
                return $item;
            });

        $transacciones = $ingresos->concat($egresos)->sortByDesc('fecha')->values();

        $totalIngresos = $ingresos->sum('monto_bs');
        $totalEgresos = $egresos->sum('monto_bs');
        $saldoDisponible = $totalIngresos - $totalEgresos;

        return $this->respuestaExitosa([
            'transacciones' => $transacciones,
            'total_ingresos_bs' => $totalIngresos,
            'total_egresos_bs' => $totalEgresos,
            'saldo_disponible_bs' => $saldoDisponible,
        ]);
    }

    /**
     * Estado de Resultados (Pérdidas y Ganancias).
     */
    public function estadoResultados(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId);
        $tasa = $condominio->tasa_cambio ?? 36.50;

        $mes = $request->get('mes', Carbon::now()->month);
        $anio = $request->get('anio', Carbon::now()->year);

        $ingresosMes = Payment::where('condominio_id', $condominioId)
            ->where('estado', 'aprobado')
            ->whereMonth('fecha_pago', $mes)
            ->whereYear('fecha_pago', $anio)
            ->sum('monto');

        $gastosPorCategoria = Expense::where('condominio_id', $condominioId)
            ->where('estado_pago', 'pagado')
            ->whereMonth('fecha_gasto', $mes)
            ->whereYear('fecha_gasto', $anio)
            ->selectRaw('categoria, sum(monto_bs) as total_bs')
            ->groupBy('categoria')
            ->get();

        $totalGastosMes = $gastosPorCategoria->sum('total_bs');
        $utilidadNetaBs = $ingresosMes - $totalGastosMes;

        return $this->respuestaExitosa([
            'periodo' => "{$anio}-" . str_pad($mes, 2, '0', STR_PAD_LEFT),
            'tasa_cambio' => $tasa,
            'ingresos_totales_bs' => $ingresosMes,
            'ingresos_totales_usd' => $ingresosMes / $tasa,
            'gastos_por_categoria' => $gastosPorCategoria,
            'gastos_totales_bs' => $totalGastosMes,
            'gastos_totales_usd' => $totalGastosMes / $tasa,
            'utilidad_neta_bs' => $utilidadNetaBs,
            'utilidad_neta_usd' => $utilidadNetaBs / $tasa,
        ]);
    }

    /**
     * Cuentas por cobrar y antigüedad de deuda de los propietarios.
     */
    public function cuentasPorCobrar(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();
        $tasa = (float)($condominio?->tasa_cambio ?? 36.50);
        $diasGracia = (int)($condominio?->dias_gracia ?? 5);
        $porcentajeMora = (float)($condominio?->porcentaje_mora ?? 5.00);

        $invoices = Invoice::with(['apartamento.propietarios'])
            ->where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->map(function ($inv) use ($tasa, $diasGracia, $porcentajeMora) {
                $diasVencido = Carbon::now()->diffInDays($inv->fecha_vencimiento, false);
                $esMoroso = $diasVencido < -$diasGracia;
                $deudaPendienteBs = $inv->monto_total - $inv->monto_pagado;
                
                // Cálculo de intereses por mora si aplica
                $interesMoraBs = 0;
                if ($esMoroso && ($porcentajeMora > 0)) {
                    $interesMoraBs = $deudaPendienteBs * ($porcentajeMora / 100);
                }

                $inv->dias_vencido = abs($diasVencido);
                $inv->es_moroso = $esMoroso;
                $inv->deuda_pendiente_bs = $deudaPendienteBs;
                $inv->interes_mora_bs = $interesMoraBs;
                $inv->total_a_pagar_bs = $deudaPendienteBs + $interesMoraBs;
                $inv->total_a_pagar_usd = ($deudaPendienteBs + $interesMoraBs) / $tasa;
                return $inv;
            });

        $totalDeudaBs = $invoices->sum('total_a_pagar_bs');

        return $this->respuestaExitosa([
            'facturas_pendientes' => $invoices,
            'total_deuda_bs' => $totalDeudaBs,
            'total_deuda_usd' => $totalDeudaBs / $tasa,
            'total_apartamentos_morosos' => $invoices->where('es_moroso', true)->pluck('apartamento_id')->unique()->count(),
        ]);
    }
}
