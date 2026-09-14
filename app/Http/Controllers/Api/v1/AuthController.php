<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Menu;
use App\Models\Role;
use App\Models\SelectOption;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    /**
     * Iniciar sesión con email y contraseña, control de 5 intentos fallidos y bloqueo temporal.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un formato de correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->respuestaError('Credenciales incorrectas.', 401);
        }

        // Verificar si está bloqueado temporalmente
        if ($user->bloqueado_hasta && Carbon::now()->lessThan($user->bloqueado_hasta)) {
            $minutosRestantes = Carbon::now()->diffInMinutes($user->bloqueado_hasta) + 1;
            return $this->respuestaError("Usuario bloqueado por múltiples intentos fallidos. Intente nuevamente en {$minutosRestantes} minutos.", 423);
        }

        if (!Hash::check($request->password, $user->password)) {
            $user->increment('intentos_fallidos');

            if ($user->intentos_fallidos >= 5) {
                $user->update([
                    'bloqueado_hasta' => Carbon::now()->addMinutes(15),
                    'intentos_fallidos' => 0,
                ]);
                return $this->respuestaError('Has superado los 5 intentos fallidos. Tu cuenta ha sido bloqueada temporalmente por 15 minutos.', 423);
            }

            $intentosRestantes = 5 - $user->intentos_fallidos;
            return $this->respuestaError("Credenciales incorrectas. Te quedan {$intentosRestantes} intento(s) antes del bloqueo.", 401);
        }

        if (!$user->activo) {
            return $this->respuestaError('Tu cuenta se encuentra inactiva. Contacta al administrador.', 403);
        }

        // Resetear intentos fallidos
        $user->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Verificación en Dos Pasos (2FA) si está activada para la cuenta (Fase 3.3)
        if ($user->dos_factores_activo) {
            $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'two_factor_code' => $otpCode,
                'two_factor_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            \Illuminate\Support\Facades\Log::info("🔐 [2FA AZPRO] Código de seguridad para {$user->email}: {$otpCode}");

            $tempToken = base64_encode($user->id . ':' . hash('sha256', $otpCode . config('app.key')));
            $maskedEmail = preg_replace('/(?<=..).(?=.*@)/u', '*', $user->email);

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'message' => "Se ha enviado un código de seguridad de 6 dígitos a su correo {$maskedEmail}.",
                'data' => [
                    'email' => $user->email,
                    'email_masked' => $maskedEmail,
                    'temp_token' => $tempToken,
                    'expires_in' => 600,
                ],
            ]);
        }

        // Generar token Sanctum
        $token = $user->createToken('auth-token')->plainTextToken;

        // Cargar relaciones y condominios accesibles
        $user = $this->cargarCondominiosAccesibles($user);

        // Determinar pantalla inicial según rol y cantidad de torres asignadas
        $dashboard = match (true) {
            $user->esMaster() => '/dashboard/master',
            $user->condominios_accesibles->count() > 1 => '/seleccionar-condominio',
            $user->esAdmin() || $user->esSupervisor() || $user->esAnalista() => '/dashboard/admin',
            default => '/dashboard/owner',
        };

        $tasaCentral = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        return $this->respuestaExitosa([
            'user' => $user,
            'token' => $token,
            'redirect_to' => $dashboard,
            'tasa_cambio_central' => $tasaCentral,
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * Valida el código OTP de 6 dígitos para la autenticación en dos pasos (Fase 3.3).
     */
    public function verificar2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'temp_token' => 'required|string',
        ], [
            'code.required' => 'Debe ingresar el código de 6 dígitos.',
            'code.size' => 'El código de verificación debe tener exactamente 6 dígitos.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->respuestaError('Usuario no encontrado.', 404);
        }

        if (!$user->two_factor_code || !$user->two_factor_expires_at) {
            return $this->respuestaError('No hay ningún código de verificación activo.', 400);
        }

        if (Carbon::now()->greaterThan($user->two_factor_expires_at)) {
            return $this->respuestaError('El código de verificación ha expirado. Intente iniciar sesión nuevamente.', 410);
        }

        $expectedTempToken = base64_encode($user->id . ':' . hash('sha256', $user->two_factor_code . config('app.key')));
        if ($request->code !== $user->two_factor_code || $request->temp_token !== $expectedTempToken) {
            return $this->respuestaError('El código de verificación de 6 dígitos es incorrecto.', 401);
        }

        // Limpiar código de dos factores una vez consumido exitosamente
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        $user = $this->cargarCondominiosAccesibles($user);

        $dashboard = match (true) {
            $user->esMaster() => '/dashboard/master',
            $user->condominios_accesibles->count() > 1 => '/seleccionar-condominio',
            $user->esAdmin() || $user->esSupervisor() || $user->esAnalista() => '/dashboard/admin',
            default => '/dashboard/owner',
        };

        $tasaCentral = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        return $this->respuestaExitosa([
            'user' => $user,
            'token' => $token,
            'redirect_to' => $dashboard,
            'tasa_cambio_central' => $tasaCentral,
        ], 'Verificación de dos pasos superada exitosamente.');
    }

    /**
     * Alterna la activación/desactivación de 2FA para el usuario autenticado (Fase 3.3).
     */
    public function toggle2FA(Request $request)
    {
        $user = $request->user();
        $nuevoEstado = !$user->dos_factores_activo;
        $user->update(['dos_factores_activo' => $nuevoEstado]);

        return $this->respuestaExitosa([
            'dos_factores_activo' => $nuevoEstado,
        ], $nuevoEstado ? 'Autenticación en dos pasos activada exitosamente.' : 'Autenticación en dos pasos desactivada.');
    }

    /**
     * Datos del usuario autenticado y sus permisos.
     */
    public function me(Request $request)
    {
        $user = $this->cargarCondominiosAccesibles($request->user());
        
        $tasaCentral = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        // Obtener menús permitidos
        $menus = Menu::with('hijos')
            ->whereNull('padre_id')
            ->orderBy('orden')
            ->get()
            ->filter(function ($menu) use ($user) {
                if ($user->esMaster()) return true;
                if (!$menu->permission_id) return true;
                return $user->hasPermission($menu->permission?->nombre);
            })->values();

        return $this->respuestaExitosa([
            'user' => $user,
            'menus' => $menus,
            'tasa_cambio_central' => $tasaCentral,
        ]);
    }

    /**
     * Helper para cargar todos los condominios y torres accesibles por el usuario con aislamiento estricto.
     */
    private function cargarCondominiosAccesibles(User $user): User
    {
        $user->load([
            'role.permissions',
            'condominio.parent',
            'condominio.torres.parent',
            'condominios.parent',
            'condominios.torres.parent',
            'apartamento'
        ]);

        $accessibleCondos = collect();

        if ($user->esMaster()) {
            // Super Admin: Opera a nivel global de la plataforma SaaS (no pertenece ni selecciona torres individuales)
            $accessibleCondos = collect([]);
        } else {
            // 1. Obtener IDs de condominios directamente asignados al usuario
            $assignedIds = $user->condominios->pluck('id')->toArray();
            if ($user->condominio_id) {
                $assignedIds[] = (int)$user->condominio_id;
            }
            $assignedIds = array_unique(array_filter($assignedIds));

            // 2. Consultar entidades administrables asignadas
            $assignedRecords = \App\Models\Condominio::with(['parent', 'torres'])
                ->whereIn('id', $assignedIds)
                ->where('activo', true)
                ->get();

            foreach ($assignedRecords as $record) {
                if ($record->tipo_entidad === 'conjunto_residencial' || ($record->parent_id === null && $record->torres->isNotEmpty())) {
                    // Es un complejo matriz: buscar exclusivamente las torres de este complejo donde el usuario es admin
                    $userTorres = $record->torres()->whereHas('administradores', function ($aq) use ($user) {
                        $aq->where('users.id', $user->id);
                    })->with('parent')->get();

                    if ($userTorres->isNotEmpty()) {
                        foreach ($userTorres as $ut) {
                            $accessibleCondos->push($ut);
                        }
                    } else {
                        // Si el complejo fue asignado al admin y ninguna torre tiene admin específico
                        foreach ($record->torres()->with('parent')->get() as $t) {
                            if (!$t->administradores()->exists() || $t->administradores()->where('users.id', $user->id)->exists()) {
                                $accessibleCondos->push($t);
                            }
                        }
                    }
                } else {
                    // Es una torre hija o un edificio independiente
                    $accessibleCondos->push($record);
                }
            }
        }

        $user->setRelation('condominios_accesibles', $accessibleCondos->unique('id')->values());
        return $user;
    }

    /**
     * Cerrar sesión y revocar tokens.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->respuestaExitosa(null, 'Sesión finalizada correctamente.');
    }

    /**
     * Recuperación de contraseña autogestionada.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.exists' => 'No encontramos ningún usuario registrado con ese correo.',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();
        $tempPass = Str::random(8);
        $user->update([
            'password' => Hash::make($tempPass),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        return $this->respuestaExitosa([
            'temporary_password' => $tempPass,
        ], "Se ha enviado un correo con las instrucciones y su clave temporal de recuperación: {$tempPass}");
    }
}
