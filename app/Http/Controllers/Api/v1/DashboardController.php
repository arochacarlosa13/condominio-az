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

        // 1. Antigüedad de la deuda y morosidad segmentada
        $morosidadRangos = [
            'al_dia' => 0,
            'dias_1_30' => 0,
            'dias_31_60' => 0,
            'dias_61_90' => 0,
            'dias_mas_90' => 0,
        ];
        $morosidadMontosBs = [
            'al_dia' => 0.0,
            'dias_1_30' => 0.0,
            'dias_31_60' => 0.0,
            'dias_61_90' => 0.0,
            'dias_mas_90' => 0.0,
        ];

        $invoicesPendientes = Invoice::where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->get();

        $hoy = Carbon::now();
        foreach ($invoicesPendientes as $inv) {
            $saldoInv = max(0, (float)$inv->monto_total - (float)$inv->monto_pagado);
            $diasVenc = $inv->fecha_vencimiento ? $hoy->diffInDays($inv->fecha_vencimiento, false) : 0;
            if ($diasVenc >= 0) {
                $morosidadRangos['al_dia']++;
                $morosidadMontosBs['al_dia'] += $saldoInv;
            } else {
                $diasMora = abs($diasVenc);
                if ($diasMora <= 30) {
                    $morosidadRangos['dias_1_30']++;
                    $morosidadMontosBs['dias_1_30'] += $saldoInv;
                } elseif ($diasMora <= 60) {
                    $morosidadRangos['dias_31_60']++;
                    $morosidadMontosBs['dias_31_60'] += $saldoInv;
                } elseif ($diasMora <= 90) {
                    $morosidadRangos['dias_61_90']++;
                    $morosidadMontosBs['dias_61_90'] += $saldoInv;
                } else {
                    $morosidadRangos['dias_mas_90']++;
                    $morosidadMontosBs['dias_mas_90'] += $saldoInv;
                }
            }
        }

        // 2. Distribución de gastos por categoría (Pastel)
        $distribucionGastos = \App\Models\Expense::where('condominio_id', $condominioId)
            ->where('estado_pago', 'pagado')
            ->selectRaw("COALESCE(categoria, 'Operativos Generales') as cat, SUM(monto_bs) as total_bs")
            ->groupBy('cat')
            ->get()
            ->map(function ($item) use ($tasa) {
                return [
                    'categoria' => ucfirst($item->cat),
                    'total_bs' => round((float)$item->total_bs, 2),
                    'total_usd' => $tasa > 0 ? round((float)$item->total_bs / $tasa, 2) : 0,
                ];
            });

        // 3. Semáforo e Índice de Salud Financiera del Condominio
        $totalFacturadoMesBs = (float) Invoice::where('condominio_id', $condominioId)
            ->whereMonth('fecha_emision', $mesActual)
            ->whereYear('fecha_emision', $anioActual)
            ->sum('monto_total');

        $tasaRecaudacion = $totalFacturadoMesBs > 0 ? round(($recaudadoMesBs / $totalFacturadoMesBs) * 100, 1) : 100.0;
        
        $semaforo = match (true) {
            $tasaRecaudacion >= 80 => [
                'color' => 'verde',
                'bg' => 'emerald',
                'icon' => 'mdi-shield-check',
                'estado' => 'Saludable',
                'descripcion' => 'Excelente índice de recaudación y solvencia operativa.',
            ],
            $tasaRecaudacion >= 50 => [
                'color' => 'amarillo',
                'bg' => 'amber',
                'icon' => 'mdi-alert',
                'estado' => 'Atención Requerida',
                'descripcion' => 'Recaudación regular, activar cobranza preventiva para reducir morosidad.',
            ],
            default => [
                'color' => 'rojo',
                'bg' => 'rose',
                'icon' => 'mdi-alert-octagon',
                'estado' => 'Alerta Financiera',
                'descripcion' => 'Baja liquidez del mes, concentración de recibos pendientes por cobrar.',
            ],
        };

        // Gráfica de Estado de Pagos
        $facturasPagadas = Invoice::where('condominio_id', $condominioId)->where('estado', 'pagado')->count();
        $facturasPendientes = Invoice::where('condominio_id', $condominioId)->where('estado', 'pendiente')->where('fecha_vencimiento', '>=', Carbon::now())->count();
        $facturasVencidas = Invoice::where('condominio_id', $condominioId)->where('estado', 'pendiente')->where('fecha_vencimiento', '<', Carbon::now())->count();

        return $this->respuestaExitosa([
            'tarjetas' => [
                'total_unidades' => $totalUnidades,
                'total_propietarios' => $totalPropietarios,
                'recaudado_mes_bs' => $recaudadoMesBs,
                'recaudado_mes_usd' => $tasa > 0 ? $recaudadoMesBs / $tasa : 0,
                'pendiente_mes_bs' => max(0, $pendienteMesBs),
                'pendiente_mes_usd' => $tasa > 0 ? max(0, $pendienteMesBs) / $tasa : 0,
                'reservas_mes' => $reservasMes,
                'tasa_cambio' => $tasa,
                'moneda_base' => $condominio->moneda_base ?? 'VES',
            ],
            'semaforo_financiero' => array_merge($semaforo, [
                'ratio_recaudacion' => $tasaRecaudacion,
                'total_facturado_mes_bs' => $totalFacturadoMesBs,
                'total_facturado_mes_usd' => $tasa > 0 ? round($totalFacturadoMesBs / $tasa, 2) : 0,
            ]),
            'antiguedad_deuda' => [
                'conteos' => $morosidadRangos,
                'montos_bs' => $morosidadMontosBs,
                'montos_usd' => array_map(fn($v) => $tasa > 0 ? round($v / $tasa, 2) : 0, $morosidadMontosBs),
            ],
            'distribucion_gastos' => $distribucionGastos,
            'grafica_pagos_6_meses' => $pagosUltimos6Meses,
            'grafica_estado_pagos' => [
                'pagado' => $facturasPagadas,
                'pendiente' => $facturasPendientes,
                'vencido' => $facturasVencidas,
            ],
        ]);
    }

    /**
     * Dashboard para el Propietario / Residente (Portal Móvil Ultraliviano)
     */
    public function propietario()
    {
        $user = auth()->user()->load(['apartamento.condominio']);
        $apartamento = $user->apartamento;
        $condominio = $apartamento?->condominio;
        $tasa = (float)($condominio?->tasa_cambio ?? 36.50);

        $deudaActualBs = 0;
        $invoices = collect([]);
        if ($apartamento) {
            $invoices = Invoice::where('apartamento_id', $apartamento->id)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->get();
            $deudaActualBs = $invoices->sum('monto_total') - $invoices->sum('monto_pagado');
        }

        $ultimosPagos = Payment::where('registrado_por', $user->id)
            ->orWhere(function ($q) use ($apartamento) {
                if ($apartamento) {
                    $q->whereHas('invoice', fn($sub) => $sub->where('apartamento_id', $apartamento->id))
                      ->orWhereHas('invoices', fn($sub) => $sub->where('apartamento_id', $apartamento->id));
                }
            })
            ->orderBy('fecha_pago', 'desc')
            ->take(6)
            ->get();

        $recibosRecientes = $apartamento ? Invoice::where('apartamento_id', $apartamento->id)
            ->orderBy('fecha_emision', 'desc')
            ->take(6)
            ->get() : [];

        $cuentasBancarias = $condominio ? \App\Models\CondominioCuentaBancaria::where('condominio_id', $condominio->id)
            ->where('activo', true)
            ->get() : [];

        $proximasReservas = Reservation::with('commonArea')
            ->where('user_id', $user->id)
            ->where('fecha_reserva', '>=', Carbon::today())
            ->orderBy('fecha_reserva', 'asc')
            ->take(3)
            ->get();

        return $this->respuestaExitosa([
            'unidad' => $apartamento,
            'deuda_actual_bs' => round($deudaActualBs, 2),
            'deuda_actual_usd' => $tasa > 0 ? round($deudaActualBs / $tasa, 2) : 0,
            'solvente' => $deudaActualBs <= 0.05,
            'recibos_recientes' => $recibosRecientes,
            'cuentas_bancarias' => $cuentasBancarias,
            'ultimos_pagos' => $ultimosPagos,
            'proximas_reservas' => $proximasReservas,
            'tasa_cambio' => $tasa,
        ]);
    }
}
