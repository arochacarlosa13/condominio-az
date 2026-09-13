<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Comunicado;
use App\Models\NotificacionHistorial;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComunicadoController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();

        $query = Comunicado::query();

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        $comunicados = $query->orderBy('fecha_publicacion', 'desc')->paginate(20);
        return $this->respuestaExitosa($comunicados);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'fecha_publicacion' => 'required|date',
            'fecha_expiracion' => 'nullable|date',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'enviar_email' => 'boolean',
            'enviar_whatsapp' => 'boolean',
        ]);

        $condominioId = $this->obtenerCondominioActual();

        $archivoPath = null;
        if ($request->hasFile('archivo')) {
            $archivoPath = $request->file('archivo')->store('comunicados', 'public');
        }

        $comunicado = Comunicado::create([
            'condominio_id' => $condominioId,
            'titulo' => $request->titulo,
            'contenido' => $request->contenido,
            'fecha_publicacion' => $request->fecha_publicacion,
            'fecha_expiracion' => $request->fecha_expiracion,
            'archivo_path' => $archivoPath,
            'enviar_email' => $request->enviar_email ?? false,
            'enviar_whatsapp' => $request->enviar_whatsapp ?? false,
        ]);

        // Registrar envíos en historial de notificaciones
        $propietarios = User::where('condominio_id', $condominioId)->where('rol', 'propietario')->get();
        foreach ($propietarios as $p) {
            if ($request->enviar_email) {
                NotificacionHistorial::create([
                    'condominio_id' => $condominioId,
                    'user_id' => $p->id,
                    'tipo' => 'email',
                    'plantilla' => 'comunicado',
                    'destinatario' => $p->email,
                    'mensaje' => $request->titulo . ': ' . substr($request->contenido, 0, 100),
                    'estado' => 'enviado',
                ]);
            }
            if ($request->enviar_whatsapp && $p->telefono) {
                NotificacionHistorial::create([
                    'condominio_id' => $condominioId,
                    'user_id' => $p->id,
                    'tipo' => 'whatsapp',
                    'plantilla' => 'comunicado',
                    'destinatario' => $p->telefono,
                    'mensaje' => "COMUNICADO: *{$request->titulo}*\n{$request->contenido}",
                    'estado' => 'enviado',
                ]);
            }
        }

        return $this->respuestaExitosa($comunicado, 'Comunicado publicado y notificaciones despachadas exitosamente.', 201);
    }

    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Comunicado retirado de la cartelera.');
    }
}
