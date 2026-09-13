<?php

namespace App\Http\Controllers\Api;

use App\Models\Reservation;
use App\Models\CommonArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservaController extends BaseController
{
    /**
     * Listar reservas
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['commonArea', 'user', 'apartamento']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_reserva', $request->fecha);
        }

        $reservas = $query->latest()->paginate(15);

        return $this->respuestaExitosa($reservas, 'Reservas recuperadas exitosamente');
    }

    /**
     * Crear reserva
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'common_area_id' => 'required|exists:common_areas,id',
            'apartamento_id' => 'required|exists:apartamentos,id',
            'fecha_reserva' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'numero_personas' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $area = CommonArea::find($request->common_area_id);

        // Validar capacidad
        if ($request->numero_personas > $area->capacidad_maxima) {
            return $this->respuestaError('Excede la capacidad máxima del área', 400);
        }

        // Validar conflicto de horario
        $conflicto = Reservation::where('common_area_id', $request->common_area_id)
                                ->where('fecha_reserva', $request->fecha_reserva)
                                ->where('estado', '!=', 'rechazada')
                                ->where(function ($query) use ($request) {
                                    $query->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                                          ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin]);
                                })
                                ->exists();

        if ($conflicto) {
            return $this->respuestaError('Ya existe una reserva en ese horario', 400);
        }

        $reserva = Reservation::create([
            ...$request->all(),
            'condominio_id' => $area->condominio_id,
            'user_id' => auth()->id(),
            'estado' => $area->requiere_reserva ? 'pendiente' : 'aprobada',
            'costo_total' => $area->costo_reserva,
        ]);

        return $this->respuestaExitosa($reserva, 'Reserva creada exitosamente', 201);
    }

    /**
     * Mostrar reserva
     */
    public function show(Reservation $reservation)
    {
        return $this->respuestaExitosa($reservation->load(['commonArea', 'user', 'apartamento']));
    }

    /**
     * Aprobar reserva (admin)
     */
    public function aprobar(Reservation $reservation)
    {
        if ($reservation->estado !== 'pendiente') {
            return $this->respuestaError('Solo se pueden aprobar reservas pendientes', 400);
        }

        $reservation->update(['estado' => 'aprobada']);

        return $this->respuestaExitosa($reservation, 'Reserva aprobada exitosamente');
    }

    /**
     * Rechazar reserva (admin)
     */
    public function rechazar(Request $request, Reservation $reservation)
    {
        if ($reservation->estado !== 'pendiente') {
            return $this->respuestaError('Solo se pueden rechazar reservas pendientes', 400);
        }

        $reservation->update([
            'estado' => 'rechazada',
            'notas' => $request->motivo ?? 'Reserva rechazada por administración',
        ]);

        return $this->respuestaExitosa($reservation, 'Reserva rechazada');
    }

    /**
     * Cancelar reserva (propietario)
     */
    public function destroy(Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            return $this->respuestaError('No autorizado', 403);
        }

        $reservation->update(['estado' => 'cancelada']);
        $reservation->delete();

        return $this->respuestaExitosa(null, 'Reserva cancelada exitosamente');
    }
}