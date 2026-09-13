<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditController extends BaseController
{
    /**
     * Listar auditoría (solo master)
     */
    public function index(Request $request)
    {
        $query = AuditTrail::with('user');

        // Filtros opcionales
        if ($request->filled('user_id')) {
            $query->porUsuario($request->user_id);
        }

        if ($request->filled('modelo')) {
            $query->porModelo($request->modelo);
        }

        if ($request->filled('accion')) {
            $query->porAccion($request->accion);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $auditoria = $query->latest()->paginate(20);

        return $this->respuestaExitosa($auditoria, 'Auditoría recuperada exitosamente');
    }

    /**
     * Mostrar detalle de auditoría
     */
    public function show(AuditTrail $auditTrail)
    {
        return $this->respuestaExitosa($auditTrail->load('user'), 'Detalle de auditoría');
    }

    /**
     * Auditoría por condominio (admin)
     */
    public function condominioAuditoria(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        
        $auditoria = AuditTrail::where('condominio_id', $condominioId)
                               ->with('user')
                               ->latest()
                               ->paginate(20);

        return $this->respuestaExitosa($auditoria, 'Auditoría del condominio');
    }
}