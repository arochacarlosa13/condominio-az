<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Condominio;

class BaseController extends Controller
{
    /**
     * Respuesta exitosa genérica
     */
    protected function respuestaExitosa($datos = null, string $mensaje = 'Operación exitosa', int $codigo = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $datos,
        ], $codigo);
    }

    /**
     * Respuesta de error genérica
     */
    protected function respuestaError(string $mensaje = 'Error en la operación', int $codigo = 400, $errores = null): JsonResponse
    {
        $respuesta = [
            'success' => false,
            'message' => $mensaje,
        ];

        if ($errores) {
            $respuesta['errors'] = $errores;
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Obtener el condominio / torre actual del usuario con soporte multi-tenant y validación de seguridad
     */
    protected function obtenerCondominioActual()
    {
        $user = auth()->user();

        // 1. Verificar si viene en la cabecera HTTP X-Condominio-Id o parámetro de petición
        $solicitado = request()->header('X-Condominio-Id') ?? request()->condominio_id;

        if ($user && $user->esMaster()) {
            return $solicitado ? (int)$solicitado : (Condominio::whereNull('parent_id')->orderBy('id')->value('id') ?? Condominio::orderBy('id')->value('id'));
        }

        if ($user && $solicitado) {
            $solicitado = (int)$solicitado;
            if ($user->puedeAccederCondominio($solicitado)) {
                return $solicitado;
            }
        }

        // 2. Fallback al condominio asignado por defecto o primero de la lista
        if ($user?->condominio_id) {
            return (int)$user->condominio_id;
        }

        $primerCondo = $user?->condominios()->first();
        if ($primerCondo) {
            return (int)$primerCondo->id;
        }

        return Condominio::value('id');
    }
}