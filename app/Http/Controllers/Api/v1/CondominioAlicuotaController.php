<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Condominio;
use App\Models\CondominioAlicuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CondominioAlicuotaController extends BaseController
{
    /**
     * Listar catálogo de alícuotas del condominio
     */
    public function index(Request $request, $condominioId = null)
    {
        $targetCondoId = $condominioId ?: ($request->condominio_id ?? $this->obtenerCondominioActual());
        if (!$targetCondoId) {
            return $this->respuestaError('Condominio no especificado', 422);
        }

        $condominio = Condominio::find($targetCondoId);
        if (!$condominio) {
            return $this->respuestaError('Condominio no encontrado', 404);
        }

        // Si el condominio aún no tiene alícuotas definidas, auto-crear Ali 1 por defecto
        $alicuotas = CondominioAlicuota::where('condominio_id', $targetCondoId)
            ->orderBy('numero', 'asc')
            ->get();

        if ($alicuotas->isEmpty()) {
            $ali1 = CondominioAlicuota::create([
                'condominio_id' => $targetCondoId,
                'numero' => 1,
                'nombre' => 'Gastos Generales',
                'descripcion' => 'Alícuota principal de gastos comunes',
                'activo' => true,
            ]);
            $alicuotas = collect([$ali1]);
        }

        return $this->respuestaExitosa($alicuotas);
    }

    /**
     * Crear una nueva definición de alícuota en el condominio
     */
    public function store(Request $request, $condominioId = null)
    {
        $targetCondoId = $condominioId ?: ($request->condominio_id ?? $this->obtenerCondominioActual());

        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'numero' => 'nullable|integer|min:1',
            'activo' => 'boolean',
        ]);

        $maxNumero = CondominioAlicuota::where('condominio_id', $targetCondoId)->max('numero') ?? 0;
        $numero = $request->numero ?: ($maxNumero + 1);

        // Verificar si el número ya existe
        $existe = CondominioAlicuota::where('condominio_id', $targetCondoId)
            ->where('numero', $numero)
            ->first();

        if ($existe) {
            $numero = $maxNumero + 1;
        }

        $alicuota = CondominioAlicuota::create([
            'condominio_id' => $targetCondoId,
            'numero' => $numero,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo ?? true,
        ]);

        return $this->respuestaExitosa($alicuota, 'Alícuota creada exitosamente en el catálogo del condominio.', 201);
    }

    /**
     * Actualizar nombre, descripción o estado de una alícuota
     */
    public function update(Request $request, CondominioAlicuota $condominioAlicuota)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $condominioAlicuota->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->has('activo') ? $request->activo : $condominioAlicuota->activo,
        ]);

        return $this->respuestaExitosa($condominioAlicuota, 'Alícuota actualizada exitosamente.');
    }

    /**
     * Eliminar una alícuota del catálogo
     */
    public function destroy(CondominioAlicuota $condominioAlicuota)
    {
        if ($condominioAlicuota->numero === 1) {
            return $this->respuestaError('La Alícuota #1 (Gastos Generales) es obligatoria y no puede ser eliminada.', 422);
        }

        $condominioAlicuota->delete();
        return $this->respuestaExitosa(null, 'Alícuota eliminada del catálogo.');
    }
}
