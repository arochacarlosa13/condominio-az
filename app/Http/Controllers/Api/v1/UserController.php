<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Role;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends BaseController
{
    /**
     * Listado global de usuarios con filtros avanzados y control multi-tenant
     */
    public function index(Request $request)
    {
        $query = User::with([
            'role',
            'condominio.parent',
            'condominios.parent',
            'apartamento.condominio'
        ]);

        $authUser = auth()->user();
        if ($request->boolean('solo_admins')) {
            $query->whereNotIn('rol', ['propietario', 'Propietario/Residente', 'propietario/residente']);
        } elseif ($authUser && !$authUser->esMaster()) {
            $activeCondoId = $this->obtenerCondominioActual();
            if ($activeCondoId) {
                $query->where(function ($q) use ($activeCondoId) {
                    $q->where('condominio_id', $activeCondoId)
                      ->orWhereHas('condominios', function ($cq) use ($activeCondoId) {
                          $cq->where('condominios.id', $activeCondoId);
                      });
                });
            }
            // Para administradores de condominio, mostrar únicamente los propietarios / residentes
            $query->where(function ($q) {
                $q->where('rol', 'propietario')
                  ->orWhere('rol', 'Propietario/Residente')
                  ->orWhere('rol', 'propietario/residente');
            });
        } else {
            // Filtros de Super Admin
            if ($request->has('rol') && $request->rol) {
                $query->where('rol', $request->rol);
            }

            $condoId = $request->condominio_id ?? $request->header('X-Condominio-Id');
            if ($condoId) {
                $query->where(function ($q) use ($condoId) {
                    $q->where('condominio_id', $condoId)
                      ->orWhereHas('condominios', function ($cq) use ($condoId) {
                          $cq->where('condominios.id', $condoId);
                      });
                });
            }
        }

        // Filtro por búsqueda de texto (nombre, email, cédula, teléfono o número de apartamento)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('cedula', 'ILIKE', "%{$search}%")
                  ->orWhere('telefono', 'ILIKE', "%{$search}%")
                  ->orWhereHas('apartamento', function ($aq) use ($search) {
                      $aq->where('numero', 'ILIKE', "%{$search}%")
                         ->orWhere('piso', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Filtro por apartamento específico
        if ($request->filled('apartamento_id')) {
            if ($request->apartamento_id === 'sin_apartamento') {
                $query->whereNull('apartamento_id');
            } else {
                $query->where('apartamento_id', $request->apartamento_id);
            }
        }

        // Filtro por estado activo / inactivo
        if ($request->has('status') && $request->status !== '') {
            $query->where('activo', filter_var($request->status, FILTER_VALIDATE_BOOLEAN));
        }

        // Filtro por estado de bloqueo
        if ($request->has('bloqueado') && filter_var($request->bloqueado, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('bloqueado_hasta')->where('bloqueado_hasta', '>', Carbon::now());
        }

        if ($request->boolean('all') || $request->get('per_page') === 'all') {
            $users = $query->orderBy('name', 'asc')->get();
            return $this->respuestaExitosa($users);
        }

        $perPage = (int)($request->get('per_page', 50));
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return $this->respuestaExitosa($users);
    }

    /**
     * Crear un nuevo usuario en la plataforma con restricción estricta para administradores
     */
    public function store(Request $request)
    {
        $authUser = auth()->user();

        // Si no es Master Admin, restringir forzosamente el rol a Propietario y al condominio activo
        if ($authUser && !$authUser->esMaster()) {
            $activeCondoId = $this->obtenerCondominioActual();

            if (!$activeCondoId || !$authUser->puedeAccederCondominio($activeCondoId)) {
                return $this->respuestaError('No tiene autorización para registrar usuarios en este condominio.', 403);
            }

            $request->merge([
                'rol' => 'Propietario/Residente',
                'condominio_id' => $activeCondoId,
                'condominio_ids' => [$activeCondoId],
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'rol' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'condominio_id' => 'nullable|exists:condominios,id',
            'condominio_ids' => 'nullable|array',
            'condominio_ids.*' => 'exists:condominios,id',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        // Si se asignó un apartamento, validar que pertenezca al condominio asignado
        if ($request->apartamento_id && $request->condominio_id) {
            $apto = Apartamento::find($request->apartamento_id);
            if ($apto && (int)$apto->condominio_id !== (int)$request->condominio_id) {
                return $this->respuestaError('El apartamento seleccionado no pertenece al condominio asignado.', 422);
            }
        }

        // Si se provee rol por nombre pero no role_id, buscar su role_id
        $roleId = $request->role_id;
        if (!$roleId && $request->rol) {
            $role = Role::where('nombre', $request->rol)
                        ->orWhere('slug', strtolower(str_replace([' ', '/'], ['-', '-'], $request->rol)))
                        ->first();
            $roleId = $role?->id;
        }

        $condoIds = $request->input('condominio_ids', []);
        $primaryCondoId = $request->input('condominio_id');

        if ($request->rol === 'Super Admin' || $request->rol === 'master') {
            $primaryCondoId = null;
            $condoIds = [];
            $apartamentoId = null;
        } elseif ($request->rol === 'Propietario/Residente' || $request->rol === 'propietario') {
            $condoIds = $primaryCondoId ? [$primaryCondoId] : [];
            $apartamentoId = $request->apartamento_id;
        } else {
            if (is_array($condoIds) && !empty($condoIds)) {
                $condoIds = array_values(array_unique(array_filter($condoIds)));
                if (!$primaryCondoId || !in_array($primaryCondoId, $condoIds)) {
                    $primaryCondoId = $condoIds[0];
                }
            } elseif ($primaryCondoId) {
                $condoIds = [$primaryCondoId];
            } else {
                $condoIds = [];
            }
            $apartamentoId = null;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'role_id' => $roleId,
            'condominio_id' => $primaryCondoId,
            'apartamento_id' => $apartamentoId,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'activo' => $request->activo ?? true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Sincronizar condominios asignados en la tabla pivote condominio_user
        if (!empty($condoIds)) {
            $syncData = [];
            foreach ($condoIds as $cid) {
                $syncData[$cid] = ['es_principal' => ((int)$cid === (int)$primaryCondoId)];
            }
            $user->condominios()->sync($syncData);
        } else {
            $user->condominios()->sync([]);
        }

        // Si el usuario es propietario y se asignó apartamento, crear o actualizar registro en propietarios
        if (($user->esPropietario() || $request->rol === 'propietario' || $request->rol === 'Propietario/Residente') && $apartamentoId) {
            Propietario::updateOrCreate([
                'apartamento_id' => $apartamentoId,
            ], [
                'user_id' => $user->id,
                'condominio_id' => $primaryCondoId,
                'nombre_completo' => $request->name,
                'cedula' => $request->cedula ?? 'V-00000000',
                'telefono' => $request->telefono,
                'email' => $request->email,
                'es_propietario_principal' => true,
                'fecha_inicio' => Carbon::now(),
                'activo' => true,
            ]);
        }

        return $this->respuestaExitosa($user->load(['role', 'condominio.parent', 'condominios.parent', 'apartamento']), 'Usuario registrado exitosamente.', 201);
    }

    /**
     * Actualizar datos del usuario con validación de ámbito
     */
    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        if ($authUser && !$authUser->esMaster()) {
            $activeCondoId = $this->obtenerCondominioActual();

            if ((int)$user->condominio_id !== (int)$activeCondoId || !$authUser->puedeAccederCondominio($activeCondoId)) {
                return $this->respuestaError('No tiene autorización para modificar usuarios fuera de su condominio activo.', 403);
            }

            // Forzar que el rol sea propietario y pertenezca al condominio activo
            $request->merge([
                'rol' => 'Propietario/Residente',
                'condominio_id' => $activeCondoId,
                'condominio_ids' => [$activeCondoId],
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'rol' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'condominio_id' => 'nullable|exists:condominios,id',
            'condominio_ids' => 'nullable|array',
            'condominio_ids.*' => 'exists:condominios,id',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'cedula' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);

        if ($request->apartamento_id && $request->condominio_id) {
            $apto = Apartamento::find($request->apartamento_id);
            if ($apto && (int)$apto->condominio_id !== (int)$request->condominio_id) {
                return $this->respuestaError('El apartamento seleccionado no pertenece al condominio asignado.', 422);
            }
        }

        $condoIds = $request->input('condominio_ids');
        $primaryCondoId = $request->input('condominio_id');

        if ($request->rol === 'Super Admin' || $request->rol === 'master') {
            $primaryCondoId = null;
            $condoIds = [];
            $apartamentoId = null;
        } elseif ($request->rol === 'Propietario/Residente' || $request->rol === 'propietario') {
            $condoIds = $primaryCondoId ? [$primaryCondoId] : [];
            $apartamentoId = $request->apartamento_id;
        } else {
            // Administrador, Supervisor, Analista
            if (is_array($condoIds) && !empty($condoIds)) {
                $condoIds = array_values(array_unique(array_filter($condoIds)));
                if (!$primaryCondoId || !in_array($primaryCondoId, $condoIds)) {
                    $primaryCondoId = $condoIds[0];
                }
            } elseif ($primaryCondoId) {
                $condoIds = [$primaryCondoId];
            } else {
                $condoIds = [];
            }
            $apartamentoId = null;
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'role_id' => $request->role_id ?? $user->role_id,
            'condominio_id' => $primaryCondoId,
            'apartamento_id' => $apartamentoId,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'activo' => $request->activo ?? $user->activo,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sincronizar condominios en tabla pivote
        if (!empty($condoIds)) {
            $syncData = [];
            foreach ($condoIds as $cid) {
                $syncData[$cid] = ['es_principal' => ((int)$cid === (int)$primaryCondoId)];
            }
            $user->condominios()->sync($syncData);
        } else {
            $user->condominios()->sync([]);
        }

        // Si se actualizó apartamento y es propietario, sincronizar tabla propietarios
        if ($apartamentoId && ($user->esPropietario() || $request->rol === 'Propietario/Residente' || $request->rol === 'propietario')) {
            Propietario::updateOrCreate([
                'user_id' => $user->id,
            ], [
                'apartamento_id' => $apartamentoId,
                'condominio_id' => $primaryCondoId,
                'nombre_completo' => $request->name,
                'cedula' => $request->cedula ?? $user->cedula ?? 'V-00000000',
                'telefono' => $request->telefono,
                'email' => $request->email,
                'es_propietario_principal' => true,
                'activo' => $request->activo ?? $user->activo,
            ]);
        }

        return $this->respuestaExitosa($user->load(['role', 'condominio.parent', 'condominios.parent', 'apartamento']), 'Usuario actualizado exitosamente.');
    }

    /**
     * Desbloquear usuario y reiniciar intentos fallidos
     */
    public function desbloquear(User $user)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->esMaster()) {
            $activeCondoId = $this->obtenerCondominioActual();
            if ((int)$user->condominio_id !== (int)$activeCondoId || !$authUser->puedeAccederCondominio($activeCondoId)) {
                return $this->respuestaError('No tiene autorización sobre este usuario.', 403);
            }
        }

        $user->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        return $this->respuestaExitosa($user, 'El usuario ha sido desbloqueado y sus intentos fallidos se han reiniciado.');
    }

    /**
     * Eliminar usuario lógicamente
     */
    public function destroy(User $user)
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->esMaster()) {
            $activeCondoId = $this->obtenerCondominioActual();
            if ((int)$user->condominio_id !== (int)$activeCondoId || !$authUser->puedeAccederCondominio($activeCondoId)) {
                return $this->respuestaError('No tiene autorización para eliminar este usuario.', 403);
            }
        }

        $user->delete();
        return $this->respuestaExitosa(null, 'Usuario eliminado lógicamente.');
    }
}

