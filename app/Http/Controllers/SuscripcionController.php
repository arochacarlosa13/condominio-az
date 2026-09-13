<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Plan;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuscripcionController extends Controller
{
    /**
     * Actualiza la suscripción de un condominio
     */
    public function update(Request $request, Condominio $condominio)
    {
        $request->validate([
            'plan_id' => 'required|exists:planes,id',
            'fecha_vencimiento_suscripcion' => 'nullable|date',
            'estado_suscripcion' => 'required|in:activo,vencido,suspendido',
        ]);

        $plan = Plan::find($request->plan_id);

        $condominio->update([
            'plan_id' => $request->plan_id,
            'plan_suscripcion' => $plan ? $plan->nombre : $condominio->plan_suscripcion, // Mantener string de compatibilidad
            'fecha_vencimiento_suscripcion' => $request->fecha_vencimiento_suscripcion,
            'estado_suscripcion' => $request->estado_suscripcion,
        ]);

        // Registrar en Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'modificar',
            'modulo' => 'Suscripcion',
            'detalle' => "Actualizó la suscripción de '{$condominio->nombre}' al plan '{$plan->nombre}' (ID: {$plan->id}). Vencimiento: " . ($request->fecha_vencimiento_suscripcion ?? 'N/A') . ". Estado: '{$request->estado_suscripcion}'.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Suscripción del condominio actualizada correctamente.');
    }

    /**
     * Calcula el costo estimado mensual según el número de apartamentos y plan
     */
    public static function obtenerCostoMensual(?int $planId, int $apartamentos): float
    {
        if (!$planId) {
            return 0.00;
        }
        $plan = Plan::find($planId);
        if (!$plan) {
            return 0.00;
        }

        return (float)($plan->costo_base + ($plan->costo_por_apartamento * $apartamentos));
    }
}
