<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApartamentoController extends BaseController
{
    /**
     * Listar apartamentos
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $apartamentos = Apartamento::with(['propietarios', 'condominio'])
                                   ->latest()
                                   ->paginate(15);

        return $this->respuestaExitosa($apartamentos, 'Apartamentos recuperados exitosamente');
    }

    /**
     * Crear un nuevo apartamento
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'condominio_id' => 'required|exists:condominios,id',
            'numero' => 'required|string|max:20',
            'piso' => 'nullable|string|max:10',
            'metros_cuadrados' => 'nullable|numeric|min:0',
            'habitaciones' => 'required|integer|min:1|max:10',
            'banos' => 'required|integer|min:1|max:10',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        // Verificar que el número de apartamento no exista en el condominio
        $existe = Apartamento::where('condominio_id', $request->condominio_id)
                             ->where('numero', $request->numero)
                             ->exists();

        if ($existe) {
            return $this->respuestaError('Ya existe un apartamento con ese número en este condominio', 422);
        }

        $apartamento = Apartamento::create($request->all());

        return $this->respuestaExitosa($apartamento, 'Apartamento creado exitosamente', 201);
    }

    /**
     * Mostrar un apartamento específico
     */
    public function show(Apartamento $apartamento): \Illuminate\Http\JsonResponse
    {
        $apartamento->load(['propietarios', 'condominio', 'invoices' => function ($query) {
            $query->where('estado', 'pendiente')->latest();
        }]);

        return $this->respuestaExitosa($apartamento, 'Apartamento recuperado exitosamente');
    }

    /**
     * Actualizar un apartamento
     */
    public function update(Request $request, Apartamento $apartamento): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero' => 'sometimes|required|string|max:20',
            'piso' => 'nullable|string|max:10',
            'metros_cuadrados' => 'nullable|numeric|min:0',
            'habitaciones' => 'sometimes|required|integer|min:1|max:10',
            'banos' => 'sometimes|required|integer|min:1|max:10',
            'ocupado' => 'sometimes|boolean',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        // Verificar unicidad del número si se está cambiando
        if ($request->has('numero') && $request->numero !== $apartamento->numero) {
            $existe = Apartamento::where('condominio_id', $apartamento->condominio_id)
                                 ->where('numero', $request->numero)
                                 ->where('id', '!=', $apartamento->id)
                                 ->exists();

            if ($existe) {
                return $this->respuestaError('Ya existe un apartamento con ese número en este condominio', 422);
            }
        }

        $apartamento->update($request->all());

        return $this->respuestaExitosa($apartamento, 'Apartamento actualizado exitosamente');
    }

    /**
     * Eliminar lógicamente un apartamento
     */
    public function destroy(Apartamento $apartamento): \Illuminate\Http\JsonResponse
    {
        $apartamento->delete();

        return $this->respuestaExitosa(null, 'Apartamento eliminado exitosamente');
    }
}