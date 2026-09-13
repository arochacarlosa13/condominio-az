<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Visitante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VisitanteController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();

        $query = Visitante::with(['apartamento', 'registradoPor', 'condominio']);

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('fecha')) {
            $query->whereDate('hora_entrada', $request->fecha);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nombre_completo', 'ILIKE', "%{$search}%")
                  ->orWhere('cedula', 'ILIKE', "%{$search}%")
                  ->orWhere('placa_vehiculo', 'ILIKE', "%{$search}%");
        }

        $visitantes = $query->orderBy('hora_entrada', 'desc')->paginate(20);
        return $this->respuestaExitosa($visitantes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'required|string|max:20',
            'placa_vehiculo' => 'nullable|string|max:20',
            'motivo' => 'nullable|string',
        ]);

        $condominioId = $this->obtenerCondominioActual();

        $visitante = Visitante::create([
            'condominio_id' => $condominioId,
            'apartamento_id' => $request->apartamento_id,
            'nombre_completo' => $request->nombre_completo,
            'cedula' => $request->cedula,
            'placa_vehiculo' => $request->placa_vehiculo,
            'hora_entrada' => Carbon::now(),
            'motivo' => $request->motivo,
            'registrado_por' => auth()->id(),
        ]);

        return $this->respuestaExitosa($visitante->load('apartamento'), 'Entrada de visitante registrada exitosamente.', 201);
    }

    /**
     * Registrar la salida de un visitante
     */
    public function registrarSalida(Visitante $visitante)
    {
        if ($visitante->hora_salida) {
            return $this->respuestaError('La salida ya fue registrada anteriormente.', 400);
        }

        $visitante->update(['hora_salida' => Carbon::now()]);
        return $this->respuestaExitosa($visitante, 'Salida de visitante registrada exitosamente.');
    }

    public function destroy(Visitante $visitante)
    {
        $visitante->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Registro de visitante eliminado lógicamente.');
    }
}
