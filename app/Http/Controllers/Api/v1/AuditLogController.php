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

    /**
     * Descarga de respaldo del sistema en 1-clic (Fase 3.2).
     */
    public function descargarBackup(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['super_admin', 'admin', 'junta'])) {
            return $this->respuestaError('No posee privilegios para descargar copias de seguridad del sistema.', 403);
        }

        $backupDir = storage_path('app/backups');
        if (!\Illuminate\Support\Facades\File::exists($backupDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($backupDir, 0755, true);
        }

        $archivos = \Illuminate\Support\Facades\File::files($backupDir);

        // Si no hay ninguno o el más reciente tiene más de 12 horas, generar uno nuevo inmediatamente
        $necesitaNuevo = empty($archivos);
        if (!$necesitaNuevo) {
            usort($archivos, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
            if ((time() - $archivos[0]->getMTime()) > (12 * 3600)) {
                $necesitaNuevo = true;
            }
        }

        if ($necesitaNuevo) {
            \Illuminate\Support\Facades\Artisan::call('condominio:backup');
            $archivos = \Illuminate\Support\Facades\File::files($backupDir);
            usort($archivos, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
        }

        $masReciente = $archivos[0] ?? null;
        if (!$masReciente || !\Illuminate\Support\Facades\File::exists($masReciente->getRealPath())) {
            return $this->respuestaError('No se pudo encontrar el archivo de respaldo generado.', 500);
        }

        return response()->download($masReciente->getRealPath(), $masReciente->getFilename(), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
