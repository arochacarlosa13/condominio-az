<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Obtener listado de todos los planes
     */
    public function index(Request $request)
    {
        $query = Plan::query();

        // Si no es master, solo retornar los planes activos
        if (!$request->user() || $request->user()->rol !== 'master') {
            $query->where('activo', true);
        }

        $planes = $query->orderBy('precio_mensual', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $planes
        ]);
    }

    /**
     * Crear un nuevo plan SaaS (Solo Master)
     */
    public function store(Request $request)
    {
        if ($request->user()->rol !== 'master') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'costo_por_apartamento' => 'nullable|numeric|min:0',
            'precio_mensual' => 'required|numeric|min:0',
            'max_apartamentos' => 'required|integer|min:1',
            'badge' => 'nullable|string|max:100',
            'destacado' => 'boolean',
            'caracteristicas' => 'nullable|array',
            'activo' => 'boolean',
        ]);

        $plan = Plan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan SaaS creado exitosamente.',
            'data' => $plan
        ], 201);
    }

    /**
     * Actualizar plan SaaS (Solo Master)
     */
    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'master') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'costo_por_apartamento' => 'nullable|numeric|min:0',
            'precio_mensual' => 'required|numeric|min:0',
            'max_apartamentos' => 'required|integer|min:1',
            'badge' => 'nullable|string|max:100',
            'destacado' => 'boolean',
            'caracteristicas' => 'nullable|array',
            'activo' => 'boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan SaaS actualizado exitosamente.',
            'data' => $plan
        ]);
    }

    /**
     * Eliminar o desactivar plan (Solo Master)
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'master') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $plan = Plan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan eliminado exitosamente.'
        ]);
    }
}
