<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class IncidenciaController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();

        $query = Incidencia::with(['apartamento', 'user', 'condominio']);

        if ($user->esPropietario()) {
            $query->where('user_id', $user->id);
        } elseif ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        if ($request->has('prioridad') && $request->prioridad !== '') {
            $query->where('prioridad', $request->prioridad);
        }

        $incidencias = $query->orderBy('created_at', 'desc')->paginate(20);
        return $this->respuestaExitosa($incidencias);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'nullable|in:baja,media,alta',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'foto' => 'nullable|image|max:5120',
        ]);

        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual() ?? $user->condominio_id;

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('incidencias', 'public');
        }

        $incidencia = Incidencia::create([
            'condominio_id' => $condominioId,
            'apartamento_id' => $request->apartamento_id ?? $user->apartamento_id,
            'user_id' => $user->id,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'prioridad' => $request->prioridad ?? 'media',
            'estado' => 'reportado',
            'foto_adjunta' => $fotoPath,
        ]);

        return $this->respuestaExitosa($incidencia, 'Incidencia reportada exitosamente.', 201);
    }

    public function update(Request $request, Incidencia $incidencia)
    {
        $request->validate([
            'responsable_nombre' => 'nullable|string',
            'fecha_solucion' => 'nullable|date',
            'estado' => 'required|in:reportado,en_proceso,resuelto',
            'prioridad' => 'nullable|in:baja,media,alta',
        ]);

        $incidencia->update($request->all());
        return $this->respuestaExitosa($incidencia, 'Incidencia actualizada correctamente.');
    }

    public function destroy(Incidencia $incidencia)
    {
        $incidencia->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Incidencia eliminada lógicamente.');
    }
}
