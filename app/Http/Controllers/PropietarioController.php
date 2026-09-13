<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropietarioController extends BaseController
{
    /**
     * Listar propietarios
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $propietarios = Propietario::with(['apartamento', 'user'])
                                   ->latest()
                                   ->paginate(15);

        return $this->respuestaExitosa($propietarios, 'Propietarios recuperados exitosamente');
    }

    /**
     * Crear un nuevo propietario
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'apartamento_id' => 'required|exists:apartamentos,id',
            'user_id' => 'nullable|exists:users,id',
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'es_propietario_principal' => 'boolean',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        // Si es propietario principal, desmarcar a los otros
        if ($request->es_propietario_principal) {
            Propietario::where('apartamento_id', $request->apartamento_id)
                       ->update(['es_propietario_principal' => false]);
        }

        $propietario = Propietario::create([
            ...$request->all(),
            'condominio_id' => Apartamento::find($request->apartamento_id)->condominio_id,
        ]);

        // Marcar el apartamento como ocupado
        $apartamento = Apartamento::find($request->apartamento_id);
        $apartamento->update(['ocupado' => true]);

        return $this->respuestaExitosa($propietario, 'Propietario creado exitosamente', 201);
    }

    /**
     * Mostrar un propietario específico
     */
    public function show(Propietario $propietario): \Illuminate\Http\JsonResponse
    {
        $propietario->load(['apartamento.condominio', 'user']);

        return $this->respuestaExitosa($propietario, 'Propietario recuperado exitosamente');
    }

    /**
     * Actualizar un propietario
     */
    public function update(Request $request, Propietario $propietario): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'sometimes|required|string|max:255',
            'cedula' => 'sometimes|required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'es_propietario_principal' => 'boolean',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        // Si se marca como propietario principal, desmarcar a los otros
        if ($request->es_propietario_principal && !$propietario->es_propietario_principal) {
            Propietario::where('apartamento_id', $propietario->apartamento_id)
                       ->update(['es_propietario_principal' => false]);
        }

        $propietario->update($request->all());

        return $this->respuestaExitosa($propietario, 'Propietario actualizado exitosamente');
    }

    /**
     * Eliminar lógicamente un propietario
     */
    public function destroy(Propietario $propietario): \Illuminate\Http\JsonResponse
    {
        $propietario->delete();

        return $this->respuestaExitosa(null, 'Propietario eliminado exitosamente');
    }
}