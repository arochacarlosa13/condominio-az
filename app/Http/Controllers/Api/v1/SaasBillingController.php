<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use App\Models\Condominio;
use App\Models\Plan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SaasBillingController extends BaseController
{
    /**
     * Listado de facturas SaaS con filtros
     */
    public function invoices(Request $request)
    {
        $query = SaasInvoice::with(['condominio', 'plan', 'saasPayments']);

        if ($request->has('condominio_id')) {
            $query->where('condominio_id', $request->condominio_id);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $invoices = $query->orderBy('fecha_emision', 'desc')->paginate(20);
        return $this->respuestaExitosa($invoices);
    }

    /**
     * Emitir cobro de suscripción a un condominio
     */
    public function generateInvoice(Request $request)
    {
        $request->validate([
            'condominio_id' => 'required|exists:condominios,id',
            'plan_id' => 'nullable|exists:planes,id',
            'periodo' => 'required|string',
            'monto_usd' => 'required|numeric|min:0',
            'tasa_cambio' => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
            'notas' => 'nullable|string',
        ]);

        $montoBs = $request->monto_usd * $request->tasa_cambio;
        $numFactura = 'SAAS-' . $request->condominio_id . '-' . $request->periodo . '-' . rand(100, 999);

        $invoice = SaasInvoice::create([
            'condominio_id' => $request->condominio_id,
            'plan_id' => $request->plan_id,
            'numero_factura' => $numFactura,
            'periodo' => $request->periodo,
            'fecha_emision' => Carbon::now(),
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'monto_total_usd' => $request->monto_usd,
            'monto_total_bs' => $montoBs,
            'tasa_cambio' => $request->tasa_cambio,
            'monto_pagado_usd' => 0,
            'monto_pagado_bs' => 0,
            'estado' => 'pendiente',
            'notas' => $request->notas,
        ]);

        return $this->respuestaExitosa($invoice, 'Factura de SaaS emitida exitosamente.', 201);
    }

    /**
     * Registro de pago recibido de un administrador de condominio
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'saas_invoice_id' => 'nullable|exists:saas_invoices,id',
            'condominio_id' => 'required|exists:condominios,id',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|string',
            'referencia' => 'nullable|string',
            'banco' => 'nullable|string',
            'monto_usd' => 'required|numeric|min:0',
            'tasa_cambio' => 'required|numeric|min:0.01',
            'notas' => 'nullable|string',
        ]);

        $montoBs = $request->monto_usd * $request->tasa_cambio;

        $payment = SaasPayment::create([
            'saas_invoice_id' => $request->saas_invoice_id,
            'condominio_id' => $request->condominio_id,
            'fecha_pago' => $request->fecha_pago,
            'metodo_pago' => $request->metodo_pago,
            'referencia' => $request->referencia,
            'banco' => $request->banco,
            'monto_usd' => $request->monto_usd,
            'monto_bs' => $montoBs,
            'tasa_cambio' => $request->tasa_cambio,
            'estado' => 'aprobado',
            'notas' => $request->notas,
            'registrado_por' => auth()->id(),
        ]);

        // Si está vinculado a una factura, actualizar montos y estado
        if ($request->filled('saas_invoice_id')) {
            $invoice = SaasInvoice::find($request->saas_invoice_id);
            $invoice->increment('monto_pagado_usd', $request->monto_usd);
            $invoice->increment('monto_pagado_bs', $montoBs);

            if ($invoice->monto_pagado_usd >= $invoice->monto_total_usd) {
                $invoice->update(['estado' => 'pagado']);
            }
        }

        // Actualizar el condominio como activo y renovar fecha por 30 días
        $condo = Condominio::find($request->condominio_id);
        if ($condo) {
            $nuevaFecha = Carbon::parse($condo->fecha_vencimiento_suscripcion ?? now())->addMonth();
            if ($nuevaFecha->isPast()) {
                $nuevaFecha = Carbon::now()->addMonth();
            }
            $condo->update([
                'estado_suscripcion' => 'activo',
                'fecha_vencimiento_suscripcion' => $nuevaFecha->toDateString()
            ]);
        }

        return $this->respuestaExitosa($payment, 'Pago de SaaS registrado y suscripción renovada correctamente.', 201);
    }

    /**
     * Verificar estado de alerta y fecha de corte de la suscripción del condominio activo
     */
    public function checkSubscriptionAlert(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->respuestaError('No autenticado', 401);
        }

        // Para Super Admin / Master no aplican restricciones de cobro
        if (in_array(strtolower($user->rol ?? ''), ['master', 'super admin'])) {
            return $this->respuestaExitosa([
                'is_master' => true,
                'necesita_alerta_7dias' => false,
                'necesita_popup_corte' => false,
            ]);
        }

        $condominioId = $request->header('X-Condominio-Id') ?: $user->condominio_id;
        $condo = Condominio::with('plan')->find($condominioId);

        if (!$condo) {
            return $this->respuestaExitosa([
                'necesita_alerta_7dias' => false,
                'necesita_popup_corte' => false,
            ]);
        }

        $plan = $condo->plan ?: Plan::firstWhere('nombre', 'like', '%Básico%') ?: Plan::first();
        $fechaVencimiento = $condo->fecha_vencimiento_suscripcion 
            ? Carbon::parse($condo->fecha_vencimiento_suscripcion)
            : Carbon::now()->endOfMonth();

        $diasRestantes = (int) ceil(Carbon::now()->diffInDays($fechaVencimiento, false));
        $estaVencido = $diasRestantes <= 0 || $condo->estado_suscripcion === 'vencido';
        $esAlerta7Dias = $diasRestantes > 0 && $diasRestantes <= 7;

        $montoUsd = $plan ? $plan->precio_mensual : 25.00;

        return $this->respuestaExitosa([
            'condominio_id' => $condo->id,
            'condominio_nombre' => $condo->nombre,
            'plan_nombre' => $plan ? $plan->nombre : 'Plan Básico',
            'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
            'dias_restantes' => $diasRestantes,
            'estado_suscripcion' => $condo->estado_suscripcion,
            'necesita_alerta_7dias' => $esAlerta7Dias,
            'necesita_popup_corte' => $estaVencido,
            'monto_usd' => $montoUsd,
            'datos_pago_master' => [
                'banco' => 'Banesco (0134)',
                'telefono' => '0412-0000000',
                'cedula_rif' => 'J-123456789',
                'titular' => 'Sistema AZPRO C.A.',
                'zelle' => 'pagos@azpro.com'
            ]
        ]);
    }

    /**
     * Reporte financiero y proyección de ingresos del Super Admin
     */
    public function financialSummary()
    {
        $totalCondominios = Condominio::count();
        $condominiosActivos = Condominio::where('activo', true)->count();
        $totalApartamentos = Condominio::sum('numero_apartamentos');

        $totalRecaudadoUsd = SaasPayment::where('estado', 'aprobado')->sum('monto_usd');
        $totalRecaudadoBs = SaasPayment::where('estado', 'aprobado')->sum('monto_bs');

        $deudaPendienteUsd = SaasInvoice::where('estado', 'pendiente')->sum('monto_total_usd');
        $deudaPendienteBs = SaasInvoice::where('estado', 'pendiente')->sum('monto_total_bs');

        // Proyección mensual basada en planes activos
        $proyeccionMensualUsd = Condominio::join('planes', 'condominios.plan_id', '=', 'planes.id')
            ->sum('planes.precio_mensual');
        if ($proyeccionMensualUsd <= 0) {
            $proyeccionMensualUsd = $totalApartamentos * 0.50;
        }
        $proyeccionMensualBs = $proyeccionMensualUsd * 36.50;

        return $this->respuestaExitosa([
            'total_condominios' => $totalCondominios,
            'condominios_activos' => $condominiosActivos,
            'total_apartamentos' => $totalApartamentos,
            'total_recaudado_usd' => $totalRecaudadoUsd,
            'total_recaudado_bs' => $totalRecaudadoBs,
            'deuda_pendiente_usd' => $deudaPendienteUsd,
            'deuda_pendiente_bs' => $deudaPendienteBs,
            'proyeccion_mensual_usd' => $proyeccionMensualUsd,
            'proyeccion_mensual_bs' => $proyeccionMensualBs,
        ]);
    }
}
