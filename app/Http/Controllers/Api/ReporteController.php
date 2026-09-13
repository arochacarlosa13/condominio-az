<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Apartamento;
use Illuminate\Http\Request;

class ReporteController extends BaseController
{
    /**
     * Reporte de propietarios morosos
     */
    public function morosos(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        
        $morosos = Invoice::where('condominio_id', $condominioId)
                          ->where('estado', 'pendiente')
                          ->where('fecha_vencimiento', '<', now())
                          ->with('apartamento.propietarios')
                          ->get()
                          ->groupBy('apartamento_id');

        return $this->respuestaExitosa($morosos, 'Reporte de morosos generado');
    }

    /**
     * Reporte de ingresos
     */
    public function ingresos(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        
        $ingresos = Payment::where('condominio_id', $condominioId)
                           ->when($request->filled('mes'), function ($query) use ($request) {
                               return $query->whereMonth('fecha_pago', $request->mes);
                           })
                           ->when($request->filled('año'), function ($query) use ($request) {
                               return $query->whereYear('fecha_pago', $request->año);
                           })
                           ->selectRaw('DATE(fecha_pago) as fecha, SUM(monto) as total')
                           ->groupBy('fecha')
                           ->orderBy('fecha', 'desc')
                           ->get();

        $totalIngresos = $ingresos->sum('total');

        return $this->respuestaExitosa([
            'ingresos_diarios' => $ingresos,
            'total_periodo' => $totalIngresos,
        ], 'Reporte de ingresos generado');
    }

    /**
     * Reporte de ocupación
     */
    public function ocupacion()
    {
        $condominioId = $this->obtenerCondominioActual();
        
        $total = Apartamento::where('condominio_id', $condominioId)->count();
        $ocupados = Apartamento::where('condominio_id', $condominioId)
                               ->where('ocupado', true)
                               ->count();
        $disponibles = $total - $ocupados;
        
        $porcentajeOcupacion = $total > 0 ? round(($ocupados / $total) * 100, 2) : 0;

        return $this->respuestaExitosa([
            'total_apartamentos' => $total,
            'apartamentos_ocupados' => $ocupados,
            'apartamentos_disponibles' => $disponibles,
            'porcentaje_ocupacion' => $porcentajeOcupacion,
        ], 'Reporte de ocupación generado');
    }
}