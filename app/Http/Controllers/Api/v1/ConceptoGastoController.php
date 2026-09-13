<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\CondominioConcepto;
use App\Models\Condominio;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConceptoGastoController extends BaseController
{
    /**
     * Listado de conceptos de gasto del condominio
     */
    public function index(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();

        $query = CondominioConcepto::query();
        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        $conceptos = $query->orderBy('ali')->orderBy('id')->get();
        return $this->respuestaExitosa($conceptos);
    }

    /**
     * Registrar un nuevo concepto de gasto
     */
    public function store(Request $request)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto_base' => 'required|numeric|min:0',
            'ali' => 'nullable|string|max:10',
            'tipo' => 'nullable|string|in:fijo,variable,extraordinario,no_comun',
            'categoria' => 'nullable|string|max:50',
            'vigente_desde' => 'nullable|date',
            'condominio_id' => 'nullable|exists:condominios,id',
        ]);

        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();

        $concepto = CondominioConcepto::create([
            'condominio_id' => $condominioId,
            'ali' => $request->ali ?? '1',
            'concepto' => strtoupper($request->concepto),
            'monto_base' => $request->monto_base,
            'tipo' => $request->tipo ?? 'fijo',
            'categoria' => $request->categoria ?? 'mantenimiento',
            'vigente_desde' => $request->vigente_desde ?? Carbon::now()->startOfMonth(),
            'activo' => true,
        ]);

        return $this->respuestaExitosa($concepto, 'Concepto de gasto registrado exitosamente.', 201);
    }

    /**
     * Actualizar concepto o ajustar monto (registrando cambio histórico)
     */
    public function update(Request $request, $id)
    {
        $conceptoGasto = CondominioConcepto::withoutGlobalScopes()->findOrFail($id);

        $user = auth()->user();
        if ($user && !$user->puedeAccederCondominio($conceptoGasto->condominio_id)) {
            return $this->respuestaError('No autorizado para modificar este concepto.', 403);
        }

        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto_base' => 'required|numeric|min:0',
            'ali' => 'nullable|string|max:10',
            'tipo' => 'nullable|string',
            'categoria' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        $conceptoGasto->update([
            'concepto' => strtoupper($request->concepto),
            'monto_base' => $request->monto_base,
            'ali' => $request->ali ?? $conceptoGasto->ali,
            'tipo' => $request->tipo ?? $conceptoGasto->tipo,
            'categoria' => $request->categoria ?? $conceptoGasto->categoria,
            'activo' => $request->has('activo') ? (bool)$request->activo : $conceptoGasto->activo,
        ]);

        return $this->respuestaExitosa($conceptoGasto, 'Concepto de gasto actualizado.');
    }

    /**
     * Eliminar lógicamente un concepto
     */
    public function destroy($id)
    {
        $conceptoGasto = CondominioConcepto::withoutGlobalScopes()->findOrFail($id);

        $user = auth()->user();
        if ($user && !$user->puedeAccederCondominio($conceptoGasto->condominio_id)) {
            return $this->respuestaError('No autorizado para eliminar este concepto.', 403);
        }

        $conceptoGasto->delete();
        return $this->respuestaExitosa(null, 'Concepto eliminado.');
    }
}
