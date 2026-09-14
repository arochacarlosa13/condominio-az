<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\NotificacionHistorial;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Http\Request;

class NotificacionController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();

        $query = NotificacionHistorial::with('user');

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        $historial = $query->orderBy('created_at', 'desc')->paginate(20);
        return $this->respuestaExitosa($historial);
    }

    /**
     * Envío masivo de recordatorios de cobro a todos los deudores del condominio.
     */
    public function enviarRecordatorioCobro(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        $invoices = Invoice::with(['apartamento.propietarios.user'])
            ->where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->get();

        $count = 0;
        foreach ($invoices as $inv) {
            foreach ($inv->apartamento->propietarios as $prop) {
                if ($prop->user && $prop->user->telefono) {
                    NotificacionHistorial::create([
                        'condominio_id' => $condominioId,
                        'user_id' => $prop->user->id,
                        'tipo' => 'whatsapp',
                        'plantilla' => 'recordatorio_pago',
                        'destinatario' => $prop->user->telefono,
                        'mensaje' => "Estimado(a) {$prop->nombre_completo}, le recordamos que su cuota del período {$inv->periodo} por un monto de Bs. {$inv->monto_total} se encuentra pendiente de pago.",
                        'estado' => 'enviado',
                    ]);
                    $count++;
                }
            }
        }

        return $this->respuestaExitosa(['enviados' => $count], "Se han enviado {$count} recordatorios de cobro vía WhatsApp.");
    }

    /**
     * Ejecuta el ciclo inteligente de cobranza preventiva (Fase 4.1).
     */
    public function ejecutarCobranzaPreventiva(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        \Illuminate\Support\Facades\Artisan::call('cobranza:preventiva', [
            '--condominio_id' => $condominioId,
        ]);

        return $this->respuestaExitosa(null, 'Ciclo de cobranza preventiva inteligente ejecutado exitosamente.');
    }
}
