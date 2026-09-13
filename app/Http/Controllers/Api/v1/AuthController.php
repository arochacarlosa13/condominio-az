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
