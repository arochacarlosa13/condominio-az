<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function index(Request $request)
    {
        $query = AuditTrail::with(['user', 'condominio']);

        if ($request->has('accion') && $request->accion !== '') {
            $query->where('accion', $request->accion);
        }

        if ($request->has('modulo') && $request->modulo !== '') {
            $query->where('modelo', 'ILIKE', "%{$request->modulo}%");
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'ILIKE', "%{$search}%")
                  ->orWhere('ip', 'ILIKE', "%{$search}%")
                  ->orWhere('modelo', 'ILIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);
        return $this->respuestaExitosa($logs);
    }
}
