<?php

namespace App\Http\Controllers\Api;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EncuestaController extends BaseController
{
    /**
     * Listar encuestas del condominio
     */
    public function index()
    {
        $encuestas = Poll::with(['opciones', 'creadoPor'])
                         ->withCount('votos')
                         ->latest()
                         ->paginate(15);

        return $this->respuestaExitosa($encuestas, 'Encuestas recuperadas exitosamente');
    }

    /**
     * Crear nueva encuesta
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'permitir_multiple' => 'boolean',
            'opciones' => 'required|array|min:2',
            'opciones.*.texto' => 'required|string|max:500',
            'opciones.*.orden' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $encuesta = Poll::create([
                'condominio_id' => $this->obtenerCondominioActual(),
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'permitir_multiple' => $request->permitir_multiple ?? false,
                'estado' => 'borrador',
                'creado_por' => auth()->id(),
            ]);

            foreach ($request->opciones as $opcion) {
                PollOption::create([
                    'poll_id' => $encuesta->id,
                    'texto' => $opcion['texto'],
                    'orden' => $opcion['orden'] ?? 0,
                ]);
            }

            DB::commit();

            return $this->respuestaExitosa($encuesta->load('opciones'), 'Encuesta creada exitosamente', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al crear encuesta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mostrar encuesta
     */
    public function show(Poll $poll)
    {
        $poll->load(['opciones', 'creadoPor'])
             ->loadCount('votos');

        return $this->respuestaExitosa($poll, 'Encuesta recuperada exitosamente');
    }

    /**
     * Publicar encuesta
     */
    public function publicar(Poll $poll)
    {
        if ($poll->estado !== 'borrador') {
            return $this->respuestaError('Solo se pueden publicar encuestas en borrador', 400);
        }

        $poll->update(['estado' => 'activa']);

        return $this->respuestaExitosa($poll, 'Encuesta publicada exitosamente');
    }

    /**
     * Votar en una encuesta
     */
    public function votar(Request $request, Poll $poll)
    {
        if ($poll->estado !== 'activa') {
            return $this->respuestaError('La encuesta no está activa', 400);
        }

        if (now()->lt($poll->fecha_inicio) || now()->gt($poll->fecha_fin)) {
            return $this->respuestaError('La encuesta no está en período de votación', 400);
        }

        $validator = Validator::make($request->all(), [
            'opcion_id' => 'required|exists:poll_options,id',
            'apartamento_id' => 'required|exists:apartamentos,id',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        // Verificar si ya votó (si no permite múltiple)
        if (!$poll->permitir_multiple) {
            $yaVoto = Vote::where('poll_id', $poll->id)
                          ->where('user_id', auth()->id())
                          ->exists();

            if ($yaVoto) {
                return $this->respuestaError('Ya has votado en esta encuesta', 400);
            }
        }

        $voto = Vote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $request->opcion_id,
            'user_id' => auth()->id(),
            'apartamento_id' => $request->apartamento_id,
            'condominio_id' => $poll->condominio_id,
        ]);

        return $this->respuestaExitosa($voto, 'Voto registrado exitosamente', 201);
    }

    /**
     * Ver resultados de encuesta
     */
    public function resultados(Poll $poll)
    {
        $resultados = PollOption::where('poll_id', $poll->id)
                                ->withCount('votos')
                                ->orderBy('votos_count', 'desc')
                                ->get();

        $totalVotos = $resultados->sum('votos_count');

        $resultados->transform(function ($opcion) use ($totalVotos) {
            $opcion->porcentaje = $totalVotos > 0 
                ? round(($opcion->votos_count / $totalVotos) * 100, 2) 
                : 0;
            return $opcion;
        });

        return $this->respuestaExitosa([
            'encuesta' => $poll->load('creadoPor'),
            'resultados' => $resultados,
            'total_votos' => $totalVotos,
        ], 'Resultados de la encuesta');
    }

    /**
     * Encuestas disponibles para propietarios
     */
    public function disponibles()
    {
        $condominioId = auth()->user()->condominio_id;
        
        $encuestas = Poll::where('condominio_id', $condominioId)
                         ->where('estado', 'activa')
                         ->where('fecha_inicio', '<=', now())
                         ->where('fecha_fin', '>=', now())
                         ->with(['opciones'])
                         ->get();

        return $this->respuestaExitosa($encuestas, 'Encuestas disponibles');
    }

    /**
     * Eliminar encuesta
     */
    public function destroy(Poll $poll)
    {
        $poll->delete();

        return $this->respuestaExitosa(null, 'Encuesta eliminada exitosamente');
    }
}