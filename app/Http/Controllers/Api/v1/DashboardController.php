<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use App\Models\Plan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    /**
     * Dashboard para el Super Admin
     */
    public function superAdmin()
    {
        $totalCondominios = Condominio::count();
        $condominiosActivos = Condominio::where('activo', true)->count();
        $totalApartamentos = Condominio::sum('numero_apartamentos');

        $recaudacionUsd = SaasPayment::where('estado', 'aprobado')->sum('monto_usd');
        $recaudacionBs = SaasPayment::where('estado', 'aprobado')->sum('monto_bs');

        // Condominios solventes vs morosos
        $condominiosMorosos = SaasInvoice::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', Carbon::now())
            ->pluck('condominio_id')
            ->unique()
            ->count();

        $condominiosSolventes = max(0, $totalCondominios - $condominiosMorosos);

        // Distribución de planes contratados (Gráfica de Torta)
        $planesData = Plan::withCount('condominios')->get()->map(function ($plan) {
            return [
                'nombre' => $plan->nombre,
                'cantidad' => $plan->condominios_count,
            ];
        });

        // Evolución de ingresos en los últimos 6 meses (Gráfica de Líneas)
        $ingresosMeses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $mesLabel = $date->translatedFormat('M Y');
            $totalMesUsd = SaasPayment::where('estado', 'aprobado')
                ->whereMonth('fecha_pago', $date->month)
                ->whereYear('fecha_pago', $date->year)
                ->sum('monto_usd');

            $ingresosMeses[] = [
                'mes' => $mesLabel,
                'total_usd' => (float) $totalMesUsd,
            ];
        }

        return $this->respuestaExitosa([
            'tarjetas' => [
                'total_condominios_activos' => $condominiosActivos,
                'total_apartamentos' => $totalApartamentos,
                'recaudacion_total_usd' => $recaudacionUsd,
                'recaudacion_total_bs' => $recaudacionBs,
                'condominios_solventes' => $condominiosSolventes,
                'condominios_morosos' => $condominiosMorosos,
            ],
            'grafica_solventes_morosos' => [
                'solventes' => $condominiosSolventes,
                'morosos' => $condominiosMorosos,
            ],
            'grafica_planes' => $planesData,
            'grafica_ingresos_lineas' => $ingresosMeses,
        ]);
    }

    /**
     * Dashboard para el Administrador de Condominio
     */
    public function adminCondominio()
    {
        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId);
        $tasa = $condominio->tasa_cambio ?? 36.50;

        $totalUnidades = Apartamento::where('condominio_id', $condominioId)->count();
        $totalPropietarios = User::where('condominio_id', $condominioId)->where('rol', 'propietario')->count();

        $mesActual = Carbon::now()->month;
        $anioActual = Carbon::now()->year;

        $recaudadoMesBs = Payment::where('condominio_id', $condominioId)
            ->where('estado', 'aprobado')
            ->whereMonth('fecha_pago', $mesActual)
            ->whereYear('fecha_pago', $anioActual)
            ->sum('monto');

        $pendienteMesBs = Invoice::where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereMonth('fecha_emision', $mesActual)
            ->whereYear('fecha_emision', $anioActual)
            ->sum('monto_total') - Invoice::where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereMonth('fecha_emision', $mesActual)
            ->whereYear('fecha_emision', $anioActual)
            ->sum('monto_pagado');

        $reservasMes = Reservation::where('condominio_id', $condominioId)
            ->whereMonth('fecha_reserva', $mesActual)
            ->whereYear('fecha_reserva', $anioActual)
            ->count();

        // Gráfica de Pagos por Mes (Últimos 6 meses)
        $pagosUltimos6Meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $pagado = Payment::where('condominio_id', $condominioId)
                ->where('estado', 'aprobado')
                ->whereMonth('fecha_pago', $date->month)
                ->whereYear('fecha_pago', $date->year)
                ->sum('monto');

            $pagosUltimos6Meses[] = [
                'mes' => $date->translatedFormat('M Y'),
                'total_bs' => (float) $pagado,
                'total_usd' => (float) ($pagado / $tasa),
            ];
        }

        // Gráfica de Estado de Pagos (Torta)
        $facturasPagadas = Invoice::where('condominio_id', $condominioId)->where('estado', 'pagado')->count();
        $facturasPendientes = Invoice::where('condominio_id', $condominioId)->where('estado', 'pendiente')->where('fecha_vencimiento', '>=', Carbon::now())->count();
        $facturasVencidas = Invoice::where('condominio_id', $condominioId)->where('estado', 'pendiente')->where('fecha_vencimiento', '<', Carbon::now())->count();

        return $this->respuestaExitosa([
            'tarjetas' => [
                'total_unidades' => $totalUnidades,
                'total_propietarios' => $totalPropietarios,
                'recaudado_mes_bs' => $recaudadoMesBs,
                'recaudado_mes_usd' => $recaudadoMesBs / $tasa,
                'pendiente_mes_bs' => max(0, $pendienteMesBs),
                'pendiente_mes_usd' => max(0, $pendienteMesBs) / $tasa,
                'reservas_mes' => $reservasMes,
                'tasa_cambio' => $tasa,
                'moneda_base' => $condominio->moneda_base ?? 'VES',
            ],
            'grafica_pagos_6_meses' => $pagosUltimos6Meses,
            'grafica_estado_pagos' => [
                'pagado' => $facturasPagadas,
                'pendiente' => $facturasPendientes,
                'vencido' => $facturasVencidas,
            ],
        ]);
    }

    /**
     * Dashboard para el Propietario / Residente
     */
    public function propietario()
    {
        $user = auth()->user()->load(['apartamento.condominio']);
        $apartamento = $user->apartamento;
        $condominio = $apartamento?->condominio;
        $tasa = $condominio->tasa_cambio ?? 36.50;

        $deudaActualBs = 0;
        if ($apartamento) {
            $invoices = Invoice::where('apartamento_id', $apartamento->id)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->get();
            $deudaActualBs = $invoices->sum('monto_total') - $invoices->sum('monto_pagado');
        }

        $ultimosPagos = Payment::where('registrado_por', $user->id)
            ->orderBy('fecha_pago', 'desc')
            ->take(5)
            ->get();

        $proximasReservas = Reservation::with('commonArea')
            ->where('user_id', $user->id)
            ->where('fecha_reserva', '>=', Carbon::today())
            ->orderBy('fecha_reserva', 'asc')
            ->take(3)
            ->get();

        return $this->respuestaExitosa([
            'unidad' => $apartamento,
            'deuda_actual_bs' => $deudaActualBs,
            'deuda_actual_usd' => $deudaActualBs / $tasa,
            'ultimos_pagos' => $ultimosPagos,
            'proximas_reservas' => $proximasReservas,
            'tasa_cambio' => $tasa,
        ]);
    }
}
