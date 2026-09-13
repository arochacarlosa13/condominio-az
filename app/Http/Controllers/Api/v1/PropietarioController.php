<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PropietarioController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();

        $query = Propietario::with(['apartamento', 'user', 'condominio']);

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_completo', 'ILIKE', "%{$search}%")
                  ->orWhere('cedula', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        $propietarios = $query->paginate(50);
        return $this->respuestaExitosa($propietarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'es_propietario_principal' => 'boolean',
        ]);

        $condominioId = $this->obtenerCondominioActual();

        // Crear o vincular usuario
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $request->nombre_completo,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? '123456'),
                'rol' => 'propietario',
                'condominio_id' => $condominioId,
                'apartamento_id' => $request->apartamento_id,
                'cedula' => $request->cedula,
                'telefono' => $request->telefono,
                'activo' => true,
            ]);
        }

        $propietario = Propietario::create([
            'apartamento_id' => $request->apartamento_id,
            'user_id' => $user->id,
            'condominio_id' => $condominioId,
            'nombre_completo' => $request->nombre_completo,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'es_propietario_principal' => $request->es_propietario_principal ?? true,
            'fecha_inicio' => Carbon::now(),
            'activo' => true,
        ]);

        return $this->respuestaExitosa($propietario->load(['apartamento', 'user']), 'Propietario registrado y vinculado exitosamente.', 201);
    }

    public function update(Request $request, Propietario $propietario)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'required|string|max:20',
            'telefono' => 'nullable|string',
            'email' => 'required|email',
            'es_propietario_principal' => 'boolean',
            'activo' => 'boolean',
        ]);

        $propietario->update($request->all());

        if ($propietario->user) {
            $propietario->user->update([
                'name' => $request->nombre_completo,
                'telefono' => $request->telefono,
                'cedula' => $request->cedula,
                'activo' => $request->activo ?? $propietario->user->activo,
            ]);
        }

        return $this->respuestaExitosa($propietario->load(['apartamento', 'user']), 'Propietario actualizado exitosamente.');
    }

    public function destroy(Propietario $propietario)
    {
        $propietario->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Propietario eliminado lógicamente.');
    }
}
