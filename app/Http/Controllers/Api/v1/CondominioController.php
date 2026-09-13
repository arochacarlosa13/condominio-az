<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Condominio;
use App\Models\User;
use App\Models\Apartamento;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\SelectOption;
use App\Models\AuditLog;
use App\Services\BcvRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CondominioController extends BaseController
{
    public function index(Request $request)
    {
        $query = Condominio::with([
            'plan',
            'parent',
            'torres.administradores',
            'administradores',
            'users' => function ($q) {
                $q->where('rol', 'admin')->orWhereHas('role', function ($rq) {
                    $rq->where('slug', 'admin-condominio');
                });
            }
        ]);

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('rif', 'LIKE', "%{$search}%")
                  ->orWhere('direccion', 'LIKE', "%{$search}%")
                  ->orWhere('torre_bloque', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('tipo_entidad') && $request->tipo_entidad !== '') {
            $query->where('tipo_entidad', $request->tipo_entidad);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('activo', filter_var($request->status, FILTER_VALIDATE_BOOLEAN));
        }

        $user = auth()->user();
        if ($user && !$user->esMaster()) {
            $assignedIds = $user->condominios()->pluck('condominios.id')->toArray();
            if ($user->condominio_id) {
                $assignedIds[] = (int)$user->condominio_id;
            }
            $assignedIds = array_unique(array_filter($assignedIds));

            $query->where(function ($q) use ($assignedIds) {
                $q->whereIn('id', $assignedIds)
                  ->orWhereIn('parent_id', $assignedIds)
                  ->orWhereHas('torres', function ($tq) use ($assignedIds) {
                      $tq->whereIn('condominios.id', $assignedIds);
                  });
            });
        }

        $condominios = $query->orderBy('created_at', 'desc')->paginate(50);
        return $this->respuestaExitosa($condominios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_entidad' => 'required|in:edificio_independiente,conjunto_residencial,torre_edificio',
            'parent_id' => 'nullable|exists:condominios,id',
            'torre_bloque' => 'nullable|string|max:50',
            'direccion' => 'required|string',
            'rif' => 'required|string|unique:condominios,rif',
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'numero_apartamentos' => 'required|integer|min:1',
            'cuota_mantenimiento_base' => 'required|numeric|min:0',
            'porcentaje_mora' => 'nullable|numeric|min:0',
            'dias_gracia' => 'nullable|integer|min:0',
            'moneda_base' => 'nullable|string|max:10',
            'tasa_cambio' => 'nullable|numeric|min:0.01',
            'mantener_tasa_emision_5_dias' => 'nullable|boolean',
            'dias_congelar_tasa' => 'nullable|integer|min:1',
            'banco_nombre' => 'nullable|string',
            'cuenta_bancaria_bs' => 'nullable|string',
            'cuenta_bancaria_usd' => 'nullable|string',
            'fondo_reserva_porcentaje' => 'nullable|numeric|min:0',
            'fondo_reserva_acumulado' => 'nullable|numeric|min:0',
            'notas_recibo' => 'nullable|string',
            'plan_id' => 'nullable|exists:planes,id',
            'plan_suscripcion' => 'nullable|string',
            'admin_user_id' => 'nullable|exists:users,id',
            'admin_name' => 'nullable|string',
            'admin_email' => 'nullable|email|unique:users,email',
            'admin_password' => 'nullable|string|min:6',
            'admin_cedula' => 'nullable|string',
            'admin_telefono' => 'nullable|string',
        ]);

        $tasaCentral = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        $condominio = Condominio::create([
            'parent_id' => $request->parent_id,
            'tipo_entidad' => $request->tipo_entidad,
            'torre_bloque' => $request->torre_bloque,
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'rif' => $request->rif,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'numero_apartamentos' => $request->numero_apartamentos,
            'cuota_mantenimiento_base' => $request->cuota_mantenimiento_base,
            'porcentaje_mora' => $request->porcentaje_mora ?? 5.00,
            'dias_gracia' => $request->dias_gracia ?? 5,
            'moneda_base' => $request->moneda_base ?? 'VES',
            'tasa_cambio' => $request->tasa_cambio ?? $tasaCentral,
            'mantener_tasa_emision_5_dias' => $request->has('mantener_tasa_emision_5_dias') ? (bool)$request->mantener_tasa_emision_5_dias : true,
            'dias_congelar_tasa' => $request->dias_congelar_tasa ?? 5,
            'banco_nombre' => $request->banco_nombre,
            'cuenta_bancaria_bs' => $request->cuenta_bancaria_bs,
            'cuenta_bancaria_usd' => $request->cuenta_bancaria_usd,
            'fondo_reserva_porcentaje' => $request->fondo_reserva_porcentaje ?? 10.00,
            'fondo_reserva_acumulado' => $request->fondo_reserva_acumulado ?? 0.00,
            'notas_recibo' => $request->notas_recibo,
            'plan_id' => $request->plan_id,
            'plan_suscripcion' => $request->plan_suscripcion ?? 'basico',
            'fecha_vencimiento_suscripcion' => Carbon::now()->addMonth(),
            'estado_suscripcion' => 'activo',
            'activo' => true,
        ]);

        // 1. Asignar administrador existente si fue seleccionado
        if ($request->filled('admin_user_id')) {
            $adminUser = User::find($request->admin_user_id);
            if ($adminUser) {
                $condominio->administradores()->syncWithoutDetaching([
                    $adminUser->id => ['es_principal' => true]
                ]);
                if (!$adminUser->condominio_id) {
                    $adminUser->update(['condominio_id' => $condominio->id]);
                }
            }
        }
        // 2. O registrar nuevo Administrador si se suministran credenciales
        elseif ($request->filled('admin_name') && $request->filled('admin_email')) {
            $nuevoAdmin = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password ?? '123456'),
                'rol' => 'admin',
                'condominio_id' => $condominio->id,
                'cedula' => $request->admin_cedula ?? 'V-00000000',
                'telefono' => $request->admin_telefono ?? $request->telefono,
                'activo' => true,
            ]);

            $condominio->administradores()->attach($nuevoAdmin->id, ['es_principal' => true]);
        }

        // 3. Crear torres hijas automáticas si se enviaron en el formulario del Conjunto
        if ($condominio->tipo_entidad === 'conjunto_residencial' && $request->filled('torres_hijas') && is_array($request->torres_hijas)) {
            foreach ($request->torres_hijas as $idx => $torreData) {
                $torreBloque = $torreData['torre_bloque'] ?? ('Torre ' . ($idx + 1));
                $torreNombre = !empty($torreData['nombre']) ? $torreData['nombre'] : "{$condominio->nombre} - {$torreBloque}";
                $torreRif = !empty($torreData['rif']) ? $torreData['rif'] : ($condominio->rif . '-' . ($idx + 1));
                $numAptos = (int)($torreData['numero_apartamentos'] ?? 20);

                $hija = Condominio::create([
                    'parent_id' => $condominio->id,
                    'tipo_entidad' => 'torre_edificio',
                    'torre_bloque' => $torreBloque,
                    'nombre' => $torreNombre,
                    'direccion' => $condominio->direccion,
                    'rif' => $torreRif,
                    'telefono' => $condominio->telefono,
                    'email' => $condominio->email,
                    'numero_apartamentos' => $numAptos,
                    'cuota_mantenimiento_base' => $torreData['cuota_mantenimiento_base'] ?? $condominio->cuota_mantenimiento_base,
                    'porcentaje_mora' => $condominio->porcentaje_mora,
                    'dias_gracia' => $condominio->dias_gracia,
                    'moneda_base' => $condominio->moneda_base,
                    'tasa_cambio' => $condominio->tasa_cambio,
                    'banco_nombre' => $condominio->banco_nombre,
                    'cuenta_bancaria_bs' => $condominio->cuenta_bancaria_bs,
                    'cuenta_bancaria_usd' => $condominio->cuenta_bancaria_usd,
                    'fondo_reserva_porcentaje' => $condominio->fondo_reserva_porcentaje,
                    'fondo_reserva_acumulado' => $condominio->fondo_reserva_acumulado,
                    'plan_id' => $condominio->plan_id,
                    'plan_suscripcion' => $condominio->plan_suscripcion,
                    'fecha_vencimiento_suscripcion' => $condominio->fecha_vencimiento_suscripcion,
                    'estado_suscripcion' => 'activo',
                    'activo' => true,
                ]);

                $adminIdHija = $torreData['admin_user_id'] ?? $request->admin_user_id;
                if ($adminIdHija) {
                    $hija->administradores()->syncWithoutDetaching([$adminIdHija => ['es_principal' => true]]);
                }
            }
        }

        return $this->respuestaExitosa(
            $condominio->load(['plan', 'parent', 'administradores', 'users']),
            'Condominio / Torre registrado exitosamente.',
            201
        );
    }

    public function show(Condominio $condominio)
    {
        return $this->respuestaExitosa(
            $condominio->load(['plan', 'parent', 'torres.administradores', 'administradores', 'apartamentos.propietarios'])
        );
    }

    public function update(Request $request, Condominio $condominio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_entidad' => 'nullable|in:edificio_independiente,conjunto_residencial,torre_edificio',
            'parent_id' => 'nullable|exists:condominios,id',
            'torre_bloque' => 'nullable|string|max:50',
            'direccion' => 'required|string',
            'rif' => 'required|string|unique:condominios,rif,' . $condominio->id,
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'numero_apartamentos' => 'required|integer|min:1',
            'cuota_mantenimiento_base' => 'required|numeric|min:0',
            'porcentaje_mora' => 'nullable|numeric|min:0',
            'dias_gracia' => 'nullable|integer|min:0',
            'moneda_base' => 'nullable|string',
            'tasa_cambio' => 'nullable|numeric|min:0.01',
            'mantener_tasa_emision_5_dias' => 'nullable|boolean',
            'dias_congelar_tasa' => 'nullable|integer|min:1',
            'banco_nombre' => 'nullable|string',
            'cuenta_bancaria_bs' => 'nullable|string',
            'cuenta_bancaria_usd' => 'nullable|string',
            'fondo_reserva_porcentaje' => 'nullable|numeric|min:0',
            'fondo_reserva_acumulado' => 'nullable|numeric|min:0',
            'notas_recibo' => 'nullable|string',
            'plan_id' => 'nullable|exists:planes,id',
            'plan_suscripcion' => 'nullable|string',
            'estado_suscripcion' => 'nullable|string',
            'activo' => 'boolean',
            'admin_user_id' => 'nullable|exists:users,id',
        ]);

        $condominio->update($request->except(['admin_user_id', 'torres_hijas']));

        // Si se especifica un nuevo administrador responsable del condominio/conjunto
        if ($request->filled('admin_user_id')) {
            $adminUser = User::find($request->admin_user_id);
            if ($adminUser) {
                // Asignar en la tabla pivote como administrador de esta entidad
                $condominio->administradores()->syncWithoutDetaching([
                    $adminUser->id => ['es_principal' => true]
                ]);
                if (!$adminUser->condominio_id) {
                    $adminUser->update(['condominio_id' => $condominio->id]);
                }
            }
        }

        // Si es conjunto residencial y se envían torres (crear nuevas o actualizar existentes)
        if ($condominio->tipo_entidad === 'conjunto_residencial' && $request->has('torres_hijas') && is_array($request->torres_hijas)) {
            $incomingIds = [];
            foreach ($request->torres_hijas as $idx => $torreData) {
                $torreBloque = $torreData['torre_bloque'] ?? ('Torre ' . ($idx + 1));
                $torreNombre = !empty($torreData['nombre']) ? $torreData['nombre'] : "{$condominio->nombre} - {$torreBloque}";
                $torreRif = !empty($torreData['rif']) ? $torreData['rif'] : ($condominio->rif . '-' . ($idx + 1));
                $numAptos = (int)($torreData['numero_apartamentos'] ?? 20);
                $adminId = $torreData['admin_user_id'] ?? $request->admin_user_id;

                if (!empty($torreData['id'])) {
                    // Actualizar torre existente
                    $tower = Condominio::where('parent_id', $condominio->id)->find($torreData['id']);
                    if ($tower) {
                        $tower->update([
                            'nombre' => $torreNombre,
                            'torre_bloque' => $torreBloque,
                            'numero_apartamentos' => $numAptos,
                            'cuota_mantenimiento_base' => $torreData['cuota_mantenimiento_base'] ?? $condominio->cuota_mantenimiento_base,
                            'direccion' => $condominio->direccion,
                        ]);
                        if ($adminId) {
                            $tower->administradores()->sync([$adminId => ['es_principal' => true]]);
                        }
                        $incomingIds[] = $tower->id;
                    }
                } else {
                    // Crear nueva torre dentro del conjunto
                    $hija = Condominio::create([
                        'parent_id' => $condominio->id,
                        'tipo_entidad' => 'torre_edificio',
                        'torre_bloque' => $torreBloque,
                        'nombre' => $torreNombre,
                        'direccion' => $condominio->direccion,
                        'rif' => $torreRif,
                        'telefono' => $condominio->telefono,
                        'email' => $condominio->email,
                        'numero_apartamentos' => $numAptos,
                        'cuota_mantenimiento_base' => $torreData['cuota_mantenimiento_base'] ?? $condominio->cuota_mantenimiento_base,
                        'porcentaje_mora' => $condominio->porcentaje_mora,
                        'dias_gracia' => $condominio->dias_gracia,
                        'moneda_base' => $condominio->moneda_base,
                        'tasa_cambio' => $condominio->tasa_cambio,
                        'banco_nombre' => $condominio->banco_nombre,
                        'cuenta_bancaria_bs' => $condominio->cuenta_bancaria_bs,
                        'cuenta_bancaria_usd' => $condominio->cuenta_bancaria_usd,
                        'fondo_reserva_porcentaje' => $condominio->fondo_reserva_porcentaje,
                        'fondo_reserva_acumulado' => $condominio->fondo_reserva_acumulado,
                        'plan_id' => $condominio->plan_id,
                        'plan_suscripcion' => $condominio->plan_suscripcion,
                        'fecha_vencimiento_suscripcion' => $condominio->fecha_vencimiento_suscripcion,
                        'estado_suscripcion' => 'activo',
                        'activo' => true,
                    ]);
                    if ($adminId) {
                        $hija->administradores()->syncWithoutDetaching([$adminId => ['es_principal' => true]]);
                    }
                    $incomingIds[] = $hija->id;
                }
            }
        }

        return $this->respuestaExitosa(
            $condominio->load(['plan', 'parent', 'torres.administradores', 'administradores', 'users']),
            'Condominio / Complejo actualizado exitosamente.'
        );
    }

    /**
     * Asignar o transferir la administración de una torre a un nuevo administrador conservando el histórico.
     */
    public function asignarAdministrador(Request $request, Condominio $condominio)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reemplazar_anteriores' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($request->boolean('reemplazar_anteriores')) {
            // Reemplaza los administradores anteriores asignando exclusivamente al nuevo
            $condominio->administradores()->sync([
                $user->id => ['es_principal' => true]
            ]);
        } else {
            // Agrega acceso a este administrador (ideal si administra múltiples torres)
            $condominio->administradores()->syncWithoutDetaching([
                $user->id => ['es_principal' => true]
            ]);
        }

        // Si el usuario no tenía un condominio activo por defecto, asignarlo
        if (!$user->condominio_id) {
            $user->update(['condominio_id' => $condominio->id]);
        }

        return $this->respuestaExitosa(
            $condominio->load(['administradores']),
            "Acceso de administración asignado exitosamente a {$user->name} para {$condominio->nombre}."
        );
    }

    /**
     * Obtener la tasa de cambio oficial central configurada para todo el sistema.
     */
    public function getTasaCambioCentral()
    {
        $tasaCentral = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        return $this->respuestaExitosa([
            'tasa_cambio_central' => $tasaCentral,
            'total_condominios' => Condominio::count(),
        ]);
    }

    /**
     * Actualizar la tasa oficial central del sistema y opcionalmente propagarla a todas las torres/condominios.
     */
    public function updateTasaCambioCentral(Request $request)
    {
        $request->validate([
            'tasa_cambio' => 'required|numeric|min:0.01',
            'actualizar_todas_las_torres' => 'nullable|boolean',
        ]);

        $tasa = (float) $request->tasa_cambio;

        $tasaAnterior = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        SelectOption::updateOrCreate(
            ['tipo' => 'configuracion_general', 'valor' => 'tasa_bcv_oficial'],
            ['etiqueta' => (string) $tasa, 'activo' => true]
        );

        // Guardar registro en el historial inmutable de tasas
        SelectOption::create([
            'tipo' => 'historico_tasa_bcv',
            'valor' => Carbon::now()->toDateTimeString(),
            'etiqueta' => (string) $tasa,
            'activo' => true,
        ]);

        $actualizados = 0;
        if ($request->boolean('actualizar_todas_las_torres')) {
            $actualizados = Condominio::query()->update(['tasa_cambio' => $tasa]);
        }

        // Registrar en bitácora de auditoría
        $user = auth()->user();
        AuditLog::create([
            'user_id' => $user?->id,
            'accion' => 'actualizar_tasa_bcv_central',
            'modulo' => 'Configuración de Tasas',
            'detalle' => "Tasa oficial BCV modificada de Bs. {$tasaAnterior} a Bs. {$tasa}. Sincronización masiva: " . ($request->boolean('actualizar_todas_las_torres') ? "SÍ ({$actualizados} torres actualizadas)" : "NO"),
            'ip_address' => $request->ip(),
            'created_at' => Carbon::now(),
        ]);

        return $this->respuestaExitosa([
            'tasa_cambio_central' => $tasa,
            'condominios_actualizados' => $actualizados,
        ], "Tasa BCV Central actualizada a Bs. " . number_format($tasa, 2, ',', '.') . ($actualizados ? " y sincronizada en {$actualizados} condominios/torres." : "."));
    }

    /**
     * Consultar en tiempo real la tasa oficial del BCV desde internet
     */
    public function consultarBcvEnVivo(BcvRateService $bcvService)
    {
        $res = $bcvService->obtenerTasaBcv();
        if (!$res['success']) {
            return $this->respuestaError($res['message'] ?? 'No se pudo conectar con el BCV.', 503);
        }
        return $this->respuestaExitosa($res, 'Tasa oficial del BCV consultada con éxito.');
    }

    public function updateTasaCambio(Request $request, Condominio $condominio)
    {
        $request->validate([
            'tasa_cambio' => 'required|numeric|min:0.01',
        ]);

        $condominio->update(['tasa_cambio' => $request->tasa_cambio]);
        return $this->respuestaExitosa($condominio, 'Tasa de cambio del condominio actualizada exitosamente.');
    }

    public function destroy(Condominio $condominio)
    {
        $condominio->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Condominio eliminado lógicamente.');
    }
}
