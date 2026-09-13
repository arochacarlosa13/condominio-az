<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Reservation;
use App\Models\CommonArea;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservaController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();

        $query = Reservation::with(['commonArea', 'user', 'apartamento', 'condominio']);

        if ($user->esPropietario()) {
            $query->where('user_id', $user->id);
        } elseif ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        if ($request->has('mes') && $request->has('anio')) {
            $query->whereMonth('fecha_reserva', $request->mes)
                  ->whereYear('fecha_reserva', $request->anio);
        }

        $reservas = $query->orderBy('fecha_reserva', 'desc')->paginate(20);
        return $this->respuestaExitosa($reservas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'common_area_id' => 'required|exists:common_areas,id',
            'fecha_reserva' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|string',
            'hora_fin' => 'required|string',
            'motivo' => 'required|string|max:255',
            'cantidad_personas' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();
        $area = CommonArea::findOrFail($request->common_area_id);

        if (!$area->activo) {
            return $this->respuestaError('El área común seleccionada está inactiva o en mantenimiento.', 400);
        }

        // Verificar choque de horarios en fecha
        $conflicto = Reservation::where('common_area_id', $area->id)
            ->where('fecha_reserva', $request->fecha_reserva)
            ->whereIn('estado', ['aprobada', 'pendiente'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                  ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin]);
            })->exists();

        if ($conflicto) {
            return $this->respuestaError('El horario seleccionado ya cuenta con una reserva para esta fecha.', 409);
        }

        $condominioId = $area->condominio_id;
        $montoTotal = $area->costo_reserva ?? 0;

        $reserva = Reservation::create([
            'common_area_id' => $area->id,
            'condominio_id' => $condominioId,
            'user_id' => $user->id,
            'apartamento_id' => $user->apartamento_id,
            'fecha_reserva' => $request->fecha_reserva,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'monto_total' => $montoTotal,
            'estado' => $user->esMaster() || $user->esAdmin() ? 'aprobada' : 'pendiente',
            'motivo' => $request->motivo,
            'cantidad_personas' => $request->cantidad_personas ?? 10,
        ]);

        return $this->respuestaExitosa($reserva->load(['commonArea', 'apartamento']), 'Solicitud de reserva registrada exitosamente.', 201);
    }

    public function aprobar(Reservation $reserva)
    {
        $reserva->update(['estado' => 'aprobada']);
        return $this->respuestaExitosa($reserva, 'Reserva aprobada exitosamente.');
    }

    public function rechazar(Request $request, Reservation $reserva)
    {
        $reserva->update([
            'estado' => 'rechazada',
            'observaciones' => $request->motivo_rechazo ?? 'Rechazada por la administración.',
        ]);
        return $this->respuestaExitosa($reserva, 'Reserva rechazada.');
    }

    public function cancelar(Reservation $reserva)
    {
        $user = auth()->user();
        if ($user->esPropietario() && $reserva->user_id !== $user->id) {
            return $this->respuestaError('No tienes permiso para cancelar esta reserva.', 403);
        }

        $reserva->update(['estado' => 'cancelada']);
        return $this->respuestaExitosa($reserva, 'Reserva cancelada exitosamente.');
    }

    /**
     * CRUD de Áreas Comunes
     */
    public function areas(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        $areas = CommonArea::where('condominio_id', $condominioId)->get();
        return $this->respuestaExitosa($areas);
    }

    public function storeArea(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'capacidad_maxima' => 'required|integer|min:1',
            'costo_reserva' => 'required|numeric|min:0',
            'requiere_reserva' => 'boolean',
            'activo' => 'boolean',
        ]);

        $condominioId = $this->obtenerCondominioActual();

        $area = CommonArea::create([
            'condominio_id' => $condominioId,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'capacidad_maxima' => $request->capacidad_maxima,
            'costo_reserva' => $request->costo_reserva,
            'requiere_reserva' => $request->requiere_reserva ?? true,
            'activo' => $request->activo ?? true,
        ]);

        return $this->respuestaExitosa($area, 'Área común registrada exitosamente.', 201);
    }

    public function updateArea(Request $request, CommonArea $area)
    {
        $area->update($request->all());
        return $this->respuestaExitosa($area, 'Área común actualizada exitosamente.');
    }

    public function destroyArea(CommonArea $area)
    {
        $area->delete();
        return $this->respuestaExitosa(null, 'Área común eliminada.');
    }
}
