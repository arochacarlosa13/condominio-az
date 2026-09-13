<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Condominio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CondominioController extends BaseController
{
    /**
     * Listar todos los condominios
     * Acceso: Solo Master
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $condominios = Condominio::with(['apartamentos', 'usuarios'])
                                 ->withCount('apartamentos', 'usuarios')
                                 ->latest()
                                 ->paginate(15);

        return $this->respuestaExitosa($condominios, 'Condominios recuperados exitosamente');
    }

    /**
     * Crear un nuevo condominio
     * Acceso: Solo Master
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'rif' => 'required|string|unique:condominios,rif|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'numero_apartamentos' => 'required|integer|min:1',
            'cuota_mantenimiento_base' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $condominio = Condominio::create($request->all());

        return $this->respuestaExitosa($condominio, 'Condominio creado exitosamente', 201);
    }

    /**
     * Mostrar un condominio específico
     */
    public function show(Condominio $condominio): \Illuminate\Http\JsonResponse
    {
        $condominio->load(['apartamentos', 'usuarios', 'areasComunes', 'encuestas']);
        $condominio->loadCount('apartamentos', 'propietarios', 'invoices');

        return $this->respuestaExitosa($condominio, 'Condominio recuperado exitosamente');
    }

    /**
     * Actualizar un condominio
     */
    public function update(Request $request, Condominio $condominio): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'direccion' => 'sometimes|required|string|max:500',
            'rif' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('condominios')->ignore($condominio->id),
            ],
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'numero_apartamentos' => 'sometimes|required|integer|min:1',
            'cuota_mantenimiento_base' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $condominio->update($request->all());

        return $this->respuestaExitosa($condominio, 'Condominio actualizado exitosamente');
    }

    /**
     * Eliminar lógicamente un condominio
     */
    public function destroy(Condominio $condominio): \Illuminate\Http\JsonResponse
    {
        $condominio->delete();

        return $this->respuestaExitosa(null, 'Condominio eliminado exitosamente');
    }

    /**
     * Obtener estadísticas del condominio
     */
    public function estadisticas(Condominio $condominio): \Illuminate\Http\JsonResponse
    {
        $estadisticas = [
            'total_apartamentos' => $condominio->apartamentos()->count(),
            'apartamentos_ocupados' => $condominio->apartamentos()->where('ocupado', true)->count(),
            'total_propietarios' => $condominio->propietarios()->where('activo', true)->count(),
            'total_areas_comunes' => $condominio->areasComunes()->count(),
            'facturas_pendientes' => $condominio->invoices()->where('estado', 'pendiente')->count(),
            'ingresos_mensuales' => $condominio->invoices()
                ->whereMonth('created_at', now()->month)
                ->sum('monto_pagado'),
        ];

        return $this->respuestaExitosa($estadisticas, 'Estadísticas recuperadas exitosamente');
    }
}