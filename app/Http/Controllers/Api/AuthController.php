<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Login de usuarios
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->respuestaError('Credenciales inválidas', 401);
        }

        if (!$user->activo) {
            return $this->respuestaError('Usuario desactivado', 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->respuestaExitosa([
            'user' => $user->load('condominio'),
            'token' => $token,
        ], 'Inicio de sesión exitoso');
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        return $this->respuestaExitosa($request->user()->load('condominio'));
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respuestaExitosa(null, 'Sesión cerrada exitosamente');
    }

    /**
     * Registro de propietarios
     */
    public function registerPropietario(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:20',
            'cedula' => 'required|string|max:20',
            'condominio_id' => 'required|exists:condominios,id',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => 'propietario',
            'condominio_id' => $request->condominio_id,
            'telefono' => $request->telefono,
            'cedula' => $request->cedula,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->respuestaExitosa([
            'user' => $user,
            'token' => $token,
        ], 'Registro exitoso', 201);
    }
}