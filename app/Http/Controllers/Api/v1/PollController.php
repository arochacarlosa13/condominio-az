<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\Apartamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PollController extends BaseController
{
    /**
     * Listado de asambleas y votaciones del condominio con cómputo de quórum y alícuotas.
     */
    public function index(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();
        $user = $request->user();

        $query = Poll::with(['options', 'creador', 'votes.apartamento'])
            ->orderBy('created_at', 'desc');

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        $polls = $query->get();

        // Obtener apartamento del usuario autenticado si es propietario/residente
        $userApartamentoId = $this->obtenerApartamentoUsuario($user);

        $data = $polls->map(function ($poll) use ($user, $userApartamentoId) {
            return $this->formatearPoll($poll, $user->id, $userApartamentoId);
        });

        return $this->respuestaExitosa($data);
    }

    /**
     * Detalle de una asamblea/votación específica.
     */
    public function show(Request $request, $id)
    {
        $poll = Poll::with(['options', 'creador', 'votes.apartamento', 'votes.option'])
            ->findOrFail($id);

        $user = $request->user();
        $userApartamentoId = $this->obtenerApartamentoUsuario($user);

        return $this->respuestaExitosa($this->formatearPoll($poll, $user->id, $userApartamentoId));
    }

    /**
     * Crear una nueva asamblea / votación ponderada (Fase 4.2).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'permitir_multiple' => 'nullable|boolean',
            'opciones' => 'required|array|min:2',
            'opciones.*.texto' => 'required|string|max:255',
        ], [
            'titulo.required' => 'El título de la asamblea/votación es obligatorio.',
            'descripcion.required' => 'Debe ingresar una descripción o motivo de la consulta.',
            'opciones.min' => 'Debe registrar al menos 2 opciones de respuesta.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $condominioId = $this->obtenerCondominioActual();
        if (!$condominioId) {
            return $this->respuestaError('No hay un condominio activo seleccionado.', 400);
        }

        DB::beginTransaction();
        try {
            $poll = Poll::create([
                'condominio_id' => $condominioId,
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => Carbon::parse($request->fecha_inicio),
                'fecha_fin' => Carbon::parse($request->fecha_fin),
                'estado' => 'activa',
                'permitir_multiple' => (bool)$request->permitir_multiple,
                'creado_por' => $request->user()->id,
            ]);

            foreach ($request->opciones as $idx => $opt) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'texto' => is_array($opt) ? $opt['texto'] : (string)$opt,
                    'orden' => $idx + 1,
                ]);
            }

            DB::commit();

            return $this->respuestaExitosa(
                $this->formatearPoll($poll->load(['options', 'votes.apartamento'])),
                'Asamblea / Votación aperturada exitosamente.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->respuestaError('Error al aperturar la votación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Registrar el voto del propietario ponderado por la alícuota de su inmueble.
     */
    public function votar(Request $request, $id)
    {
        $poll = Poll::with('options')->findOrFail($id);
        $user = $request->user();

        if ($poll->estado !== 'activa') {
            return $this->respuestaError('Esta consulta o asamblea ya no se encuentra activa.', 422);
        }

        $now = Carbon::now();
        if ($now->lt($poll->fecha_inicio) || $now->gt($poll->fecha_fin)) {
            return $this->respuestaError('La votación está fuera del rango de fechas hábiles permitido.', 422);
        }

        $apartamentoId = $this->obtenerApartamentoUsuario($user);
        if (!$apartamentoId) {
            return $this->respuestaError('Su usuario no tiene un apartamento asignado para ejercer el derecho al voto en este condominio.', 403);
        }

        // Verificar si la unidad ya emitió su voto
        $yaVoto = Vote::where('poll_id', $poll->id)
            ->where(function ($q) use ($user, $apartamentoId) {
                $q->where('user_id', $user->id)
                  ->orWhere('apartamento_id', $apartamentoId);
            })
            ->exists();

        if ($yaVoto) {
            return $this->respuestaError('Su inmueble ya ha registrado un voto formal en esta asamblea.', 422);
        }

        $validator = Validator::make($request->all(), [
            'opcion_id' => 'required|exists:poll_options,id',
        ], [
            'opcion_id.required' => 'Debe seleccionar una opción válida.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Opción inválida', 422, $validator->errors());
        }

        $option = PollOption::where('id', $request->opcion_id)
            ->where('poll_id', $poll->id)
            ->first();

        if (!$option) {
            return $this->respuestaError('La opción seleccionada no pertenece a esta asamblea.', 422);
        }

        Vote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'apartamento_id' => $apartamentoId,
            'condominio_id' => $poll->condominio_id,
        ]);

        $poll->refresh()->load(['options', 'votes.apartamento']);

        return $this->respuestaExitosa(
            $this->formatearPoll($poll, $user->id, $apartamentoId),
            '¡Su voto ha sido computado exitosamente según la alícuota de su inmueble!'
        );
    }

    /**
     * Cierre o finalización de una asamblea.
     */
    public function finalizar($id)
    {
        $poll = Poll::findOrFail($id);
        $poll->update(['estado' => 'finalizada']);

        return $this->respuestaExitosa(null, 'Asamblea cerrada y resultados consolidados.');
    }

    /**
     * Helper para calcular ponderaciones de alícuotas, quórum y formateo de respuesta.
     */
    private function formatearPoll(Poll $poll, ?int $userId = null, ?int $apartamentoId = null): array
    {
        $votes = $poll->votes ?? collect();
        $totalVotos = $votes->count();

        // Obtener la suma total de alícuota de los apartamentos que votaron
        $alicuotaTotalVotada = 0;
        $apartamentosVotados = [];

        foreach ($votes as $v) {
            $apto = $v->apartamento;
            if ($apto && !in_array($apto->id, $apartamentosVotados)) {
                $apartamentosVotados[] = $apto->id;
                $alicuotaTotalVotada += (float) ($apto->alicuota > 0 ? $apto->alicuota : 3.0346);
            }
        }

        // Quórum: porcentaje de alícuota sobre el 100%
        $quorumAlicuotaPct = min(100.0, round($alicuotaTotalVotada, 4));
        $quorumAlcanzado = ($quorumAlicuotaPct >= 50.0);

        // Opciones con recuento ponderado
        $opcionesData = $poll->options->map(function ($opt) use ($votes, $alicuotaTotalVotada) {
            $votosOpcion = $votes->where('poll_option_id', $opt->id);
            $conteo = $votosOpcion->count();
            
            $alicuotaOpcion = 0;
            foreach ($votosOpcion as $vo) {
                $apto = $vo->apartamento;
                $alicuotaOpcion += (float) ($apto?->alicuota > 0 ? $apto->alicuota : 3.0346);
            }

            $porcentajeSobreVotados = $alicuotaTotalVotada > 0 ? round(($alicuotaOpcion / $alicuotaTotalVotada) * 100, 2) : 0;
            $porcentajeSobreTotalCondo = round($alicuotaOpcion, 2);

            return [
                'id' => $opt->id,
                'texto' => $opt->texto,
                'orden' => $opt->orden,
                'votos_count' => $conteo,
                'alicuota_acumulada' => round($alicuotaOpcion, 4),
                'porcentaje_votos_emitidos' => $porcentajeSobreVotados,
                'porcentaje_alicuota_edificio' => $porcentajeSobreTotalCondo,
            ];
        });

        // Verificar si el usuario / apartamento ya votó
        $haVotado = false;
        $miVotoOpcionId = null;
        if ($userId || $apartamentoId) {
            $miVoto = $votes->first(function ($v) use ($userId, $apartamentoId) {
                return ($userId && $v->user_id === $userId) || ($apartamentoId && $v->apartamento_id === $apartamentoId);
            });
            if ($miVoto) {
                $haVotado = true;
                $miVotoOpcionId = $miVoto->poll_option_id;
            }
        }

        return [
            'id' => $poll->id,
            'condominio_id' => $poll->condominio_id,
            'titulo' => $poll->titulo,
            'descripcion' => $poll->descripcion,
            'fecha_inicio' => $poll->fecha_inicio?->format('Y-m-d H:i'),
            'fecha_fin' => $poll->fecha_fin?->format('Y-m-d H:i'),
            'estado' => $poll->estado,
            'permitir_multiple' => $poll->permitir_multiple,
            'creado_por' => $poll->creador?->name ?? 'Administración',
            'created_at' => $poll->created_at?->format('Y-m-d H:i'),
            'total_votos' => $totalVotos,
            'alicuota_votada_total' => round($alicuotaTotalVotada, 4),
            'quorum_alicuota_pct' => $quorumAlicuotaPct,
            'quorum_alcanzado' => $quorumAlcanzado,
            'ha_votado' => $haVotado,
            'mi_voto_opcion_id' => $miVotoOpcionId,
            'opciones' => $opcionesData,
        ];
    }

    /**
     * Localiza el apartamento asignado al usuario.
     */
    private function obtenerApartamentoUsuario($user): ?int
    {
        if ($user->apartamento_id) {
            return (int) $user->apartamento_id;
        }

        // Buscar a través de propietarios vinculados
        $propietario = \App\Models\Propietario::where('email', $user->email)
            ->with('apartamentos')
            ->first();

        if ($propietario && $propietario->apartamentos->isNotEmpty()) {
            return (int) $propietario->apartamentos->first()->id;
        }

        $apartamento = Apartamento::where('condominio_id', $this->obtenerCondominioActual())
            ->first();

        return $apartamento ? (int)$apartamento->id : null;
    }
}
