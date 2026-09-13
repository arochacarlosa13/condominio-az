<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\User;
use App\Models\Propietario;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\CommonArea;
use App\Models\Banco;
use App\Models\Plan;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class WebDashboardController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function loginForm()
    {
        if (Auth::check()) {
            return $this->redirectUserDashboard(Auth::user());
        }
        return Inertia::render('Auth/Login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->activo) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Esta cuenta se encuentra desactivada.',
                ]);
            }

            return $this->redirectUserDashboard($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    /**
     * Redirigir al usuario según su rol
     */
    protected function redirectUserDashboard($user)
    {
        if ($user->esMaster()) {
            return redirect()->intended('/master-dashboard');
        } elseif ($user->esAdmin()) {
            return redirect()->intended('/admin-dashboard');
        } elseif ($user->esPropietario()) {
            return redirect()->intended('/owner-dashboard');
        }

        Auth::logout();
        return redirect('/login');
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Master Dashboard
     */
    public function masterDashboard()
    {
        if (!Auth::user()->esMaster()) {
            return redirect('/');
        }

        $condominios = Condominio::with('plan')->withCount(['apartamentos', 'users'])->get();
        $bancos = Banco::orderBy('nombre')->get();
        $planes = Plan::orderBy('nombre')->get();
        $auditLogs = AuditLog::with('user')->latest()->take(100)->get();

        // Calcular costo SaaS mensual para cada uno de forma dinámica
        foreach ($condominios as $condo) {
            $plan = $condo->plan;
            if ($plan) {
                $condo->costo_suscripcion = $plan->costo_base + ($plan->costo_por_apartamento * ($condo->numero_apartamentos ?? 0));
            } else {
                $condo->costo_suscripcion = 0.00;
            }
        }

        return Inertia::render('MasterDashboard', [
            'condominios' => $condominios,
            'bancos' => $bancos,
            'planes' => $planes,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Admin Dashboard
     */
    public function adminDashboard()
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            return redirect('/');
        }

        $condominioId = $user->condominio_id;
        $condominio = Condominio::find($condominioId);

        // Datos del condominio
        $apartamentos = Apartamento::where('condominio_id', $condominioId)->with('propietarios')->get();
        $propietarios = Propietario::where('condominio_id', $condominioId)->get();
        $invoices = Invoice::where('condominio_id', $condominioId)->with('apartamento')->latest()->get();
        $payments = Payment::where('condominio_id', $condominioId)->with(['invoice.apartamento', 'registradoPor'])->latest()->get();
        $reservations = Reservation::where('condominio_id', $condominioId)->with(['commonArea', 'user', 'apartamento'])->latest()->get();
        $commonAreas = CommonArea::where('condominio_id', $condominioId)->get();
        $bancos = Banco::where('activo', true)->orderBy('nombre')->get();

        // Reportes / Métricas
        $reportes = [
            'total_apartamentos' => $apartamentos->count(),
            'apartamentos_ocupados' => $apartamentos->where('ocupado', true)->count(),
            'total_propietarios' => $propietarios->where('activo', true)->count(),
            'facturas_pendientes' => $invoices->where('estado', 'pendiente')->count(),
            'total_recaudado' => $payments->sum('monto'),
            'por_recaudar' => $invoices->where('estado', 'pendiente')->sum('monto_total') - $invoices->where('estado', 'pendiente')->sum('monto_pagado'),
        ];

        return Inertia::render('AdminDashboard', [
            'condominio' => $condominio,
            'apartamentos' => $apartamentos,
            'propietarios' => $propietarios,
            'invoices' => $invoices,
            'payments' => $payments,
            'reservations' => $reservations,
            'commonAreas' => $commonAreas,
            'reportes' => $reportes,
            'bancos' => $bancos,
        ]);
    }

    /**
     * Owner Dashboard
     */
    public function ownerDashboard()
    {
        $user = Auth::user();
        if (!$user->esPropietario()) {
            return redirect('/');
        }

        // Obtener el registro de propietario con su condominio
        $propietario = Propietario::where('user_id', $user->id)->with('condominio')->first();
        
        $apartamentos = [];
        $invoices = [];
        $payments = [];
        $reservations = [];
        $commonAreas = [];
        $bancos = Banco::where('activo', true)->orderBy('nombre')->get();

        if ($propietario) {
            $apartamentoId = $propietario->apartamento_id;
            $apartamentos = Apartamento::where('id', $apartamentoId)->get();
            $invoices = Invoice::where('apartamento_id', $apartamentoId)->latest()->get();
            $payments = Payment::where('registrado_por', $user->id)->with('invoice')->latest()->get();
            $reservations = Reservation::where('user_id', $user->id)->with('commonArea')->latest()->get();
            $commonAreas = CommonArea::where('condominio_id', $user->condominio_id)->where('activo', true)->get();
        }

        return Inertia::render('OwnerDashboard', [
            'propietario' => $propietario,
            'apartamentos' => $apartamentos,
            'invoices' => $invoices,
            'payments' => $payments,
            'reservations' => $reservations,
            'commonAreas' => $commonAreas,
            'bancos' => $bancos,
        ]);
    }

    /**
     * Crear condominio
     */
    public function storeCondominio(Request $request)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'rif' => 'required|string|unique:condominios,rif|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'numero_apartamentos' => 'required|integer|min:1',
            'cuota_mantenimiento_base' => 'required|numeric|min:0',
        ]);

        // Asignar plan gratuito por defecto
        $planGratuito = Plan::where('nombre', 'gratuito')->first();
        $data['plan_id'] = $planGratuito ? $planGratuito->id : null;
        $data['plan_suscripcion'] = 'gratuito';
        $data['estado_suscripcion'] = 'activo';
        $data['fecha_vencimiento_suscripcion'] = now()->addMonth()->toDateString();

        $condo = Condominio::create($data);

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'crear',
            'modulo' => 'Condominio',
            'detalle' => "Registró el condominio '{$condo->nombre}' (RIF: {$condo->rif}) con plan gratuito por defecto.",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Condominio creado exitosamente.');
    }

    /**
     * Actualizar condominio
     */
    public function updateCondominio(Request $request, $id)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $condominio = Condominio::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
            'rif' => 'required|string|max:20|unique:condominios,rif,' . $condominio->id,
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'numero_apartamentos' => 'required|integer|min:1',
            'cuota_mantenimiento_base' => 'required|numeric|min:0',
            'activo' => 'required|boolean',
        ]);

        $condominio->update($data);

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'modificar',
            'modulo' => 'Condominio',
            'detalle' => "Actualizó la información general del condominio '{$condominio->nombre}' (ID: {$condominio->id}).",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Condominio actualizado exitosamente.');
    }

    /**
     * Crear apartamento
     */
    public function storeApartamento(Request $request)
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            abort(403);
        }

        $condominio = Condominio::find($user->condominio_id);
        if ($condominio && $condominio->estado_suscripcion !== 'activo') {
            return redirect()->back()->with('error', 'Acción bloqueada: la suscripción del condominio no está activa.');
        }

        $data = $request->validate([
            'numero' => 'required|string|max:20',
            'piso' => 'required|string|max:20',
            'metros_cuadrados' => 'required|numeric|min:0',
            'habitaciones' => 'required|integer|min:0',
            'banos' => 'required|integer|min:0',
            'ocupado' => 'required|boolean',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $data['condominio_id'] = $user->condominio_id;

        Apartamento::create($data);

        return redirect()->back()->with('success', 'Apartamento creado exitosamente.');
    }

    /**
     * Registrar propietario y crear su cuenta de usuario
     */
    public function storePropietario(Request $request)
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            abort(403);
        }

        $condominio = Condominio::find($user->condominio_id);
        if ($condominio && $condominio->estado_suscripcion !== 'activo') {
            return redirect()->back()->with('error', 'Acción bloqueada: la suscripción del condominio no está activa.');
        }

        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Crear la cuenta de usuario
        $newUser = User::create([
            'name' => $request->nombre_completo,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => 'propietario',
            'condominio_id' => $user->condominio_id,
            'telefono' => $request->telefono,
            'cedula' => $request->cedula,
        ]);

        // Registrar como propietario
        Propietario::create([
            'apartamento_id' => $request->apartamento_id,
            'user_id' => $newUser->id,
            'condominio_id' => $user->condominio_id,
            'nombre_completo' => $request->nombre_completo,
            'cedula' => $request->cedula,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'es_propietario_principal' => true,
            'fecha_inicio' => now(),
            'activo' => true,
        ]);

        // Marcar apartamento como ocupado
        $apartamento = Apartamento::find($request->apartamento_id);
        $apartamento->update(['ocupado' => true]);

        return redirect()->back()->with('success', 'Propietario registrado exitosamente.');
    }

    /**
     * Crear Factura
     */
    public function storeInvoice(Request $request)
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            abort(403);
        }

        $condominio = Condominio::find($user->condominio_id);
        if ($condominio && $condominio->estado_suscripcion !== 'activo') {
            return redirect()->back()->with('error', 'Acción bloqueada: la suscripción del condominio no está activa.');
        }

        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'descripcion' => 'required|string|max:500',
            'periodo' => 'required|string|max:6', // 202401
            'fecha_vencimiento' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
        ]);

        Invoice::create([
            'apartamento_id' => $request->apartamento_id,
            'condominio_id' => $user->condominio_id,
            'numero_factura' => 'FAC-' . strtoupper(uniqid()),
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'monto_total' => $request->monto_total,
            'monto_pagado' => 0,
            'estado' => 'pendiente',
            'descripcion' => $request->descripcion,
            'periodo' => $request->periodo,
        ]);

        return redirect()->back()->with('success', 'Factura generada exitosamente.');
    }

    /**
     * Registrar Pago
     */
    public function storePayment(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:efectivo,transferencia,pago_movil,deposito,otro',
            'referencia' => 'nullable|string|max:50',
            'banco' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        // Si el usuario es propietario, verificar que sea dueño del apartamento de la factura
        if ($user->esPropietario()) {
            $propietario = Propietario::where('user_id', $user->id)->first();
            if (!$propietario || $propietario->apartamento_id !== $invoice->apartamento_id) {
                abort(403);
            }
        }

        $condominio = Condominio::find($invoice->condominio_id);
        $tasaActual = $condominio ? $condominio->tasa_cambio : 36.50;

        // Crear pago
        Payment::create([
            'invoice_id' => $invoice->id,
            'condominio_id' => $invoice->condominio_id,
            'monto' => $request->monto,
            'tasa_cambio' => $tasaActual,
            'fecha_pago' => now()->toDateString(),
            'metodo_pago' => $request->metodo_pago,
            'referencia' => $request->referencia,
            'banco' => $request->banco,
            'observaciones' => $request->observaciones,
            'registrado_por' => $user->id,
        ]);

        // Actualizar factura
        $nuevoMontoPagado = $invoice->monto_pagado + $request->monto;
        $estado = 'parcial';

        if ($nuevoMontoPagado >= $invoice->monto_total) {
            $nuevoMontoPagado = $invoice->monto_total;
            $estado = 'pagado';
        }

        $invoice->update([
            'monto_pagado' => $nuevoMontoPagado,
            'estado' => $estado
        ]);

        return redirect()->back()->with('success', 'Pago registrado exitosamente.');
    }

    /**
     * Solicitar Reserva (Owner)
     */
    public function storeReservation(Request $request)
    {
        $user = Auth::user();
        if (!$user->esPropietario()) {
            abort(403);
        }

        $condominio = Condominio::find($user->condominio_id);
        if ($condominio && $condominio->estado_suscripcion !== 'activo') {
            return redirect()->back()->with('error', 'Acción bloqueada: la suscripción de este condominio no está activa.');
        }

        $request->validate([
            'common_area_id' => 'required|exists:common_areas,id',
            'fecha_reserva' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|string',
            'hora_fin' => 'required|string',
            'numero_personas' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:500',
        ]);

        $area = CommonArea::findOrFail($request->common_area_id);
        $propietario = Propietario::where('user_id', $user->id)->firstOrFail();

        Reservation::create([
            'common_area_id' => $area->id,
            'user_id' => $user->id,
            'apartamento_id' => $propietario->apartamento_id,
            'condominio_id' => $user->condominio_id,
            'fecha_reserva' => $request->fecha_reserva,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'estado' => 'pendiente',
            'motivo' => $request->motivo,
            'numero_personas' => $request->numero_personas,
            'costo_total' => $area->costo_reserva,
            'pago_realizado' => false,
        ]);

        return redirect()->back()->with('success', 'Solicitud de reserva enviada correctamente.');
    }

    /**
     * Actualizar estado de Reserva (Admin)
     */
    public function updateReservationStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            abort(403);
        }

        $request->validate([
            'estado' => 'required|in:aprobada,rechazada,cancelada',
            'notas' => 'nullable|string|max:500',
        ]);

        $reserva = Reservation::findOrFail($id);
        
        $reserva->update([
            'estado' => $request->estado,
            'notas' => $request->notas,
        ]);

        return redirect()->back()->with('success', 'Estado de la reserva actualizado.');
    }

    /**
     * Actualizar tasa de cambio del dólar en el condominio (para el Admin)
     */
    public function updateTasaCambio(Request $request)
    {
        $user = Auth::user();
        if (!$user->esAdmin()) {
            abort(403);
        }

        $request->validate([
            'tasa_cambio' => 'required|numeric|min:0.01',
        ]);

        $condominio = Condominio::findOrFail($user->condominio_id);
        $condominio->update([
            'tasa_cambio' => $request->tasa_cambio,
        ]);

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => $user->id,
            'accion' => 'modificar',
            'modulo' => 'TasaCambio',
            'detalle' => "Actualizó la tasa de cambio del condominio '{$condominio->nombre}' a Bs. {$request->tasa_cambio} por USD.",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Tasa de cambio actualizada correctamente.');
    }

    /**
     * Crear Plan (Master Admin)
     */
    public function storePlan(Request $request)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:planes,nombre',
            'descripcion' => 'nullable|string|max:500',
            'costo_base' => 'required|numeric|min:0',
            'costo_por_apartamento' => 'required|numeric|min:0',
            'activo' => 'required|boolean',
        ]);

        $plan = Plan::create($data);

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'crear',
            'modulo' => 'Plan',
            'detalle' => "Creó el plan de suscripción '{$plan->nombre}' (Costo Base: \${$plan->costo_base}, Costo/Apto: \${$plan->costo_por_apartamento}).",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Plan creado exitosamente.');
    }

    /**
     * Actualizar Plan (Master Admin)
     */
    public function updatePlan(Request $request, $id)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:planes,nombre,' . $plan->id,
            'descripcion' => 'nullable|string|max:500',
            'costo_base' => 'required|numeric|min:0',
            'costo_por_apartamento' => 'required|numeric|min:0',
            'activo' => 'required|boolean',
        ]);

        $plan->update($data);

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'modificar',
            'modulo' => 'Plan',
            'detalle' => "Actualizó el plan '{$plan->nombre}' (ID: {$plan->id}). Nuevo Costo Base: \${$plan->costo_base}, Costo/Apto: \${$plan->costo_por_apartamento}.",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Plan actualizado exitosamente.');
    }

    /**
     * Eliminar Plan (Master Admin)
     */
    public function destroyPlan(Request $request, $id)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $plan = Plan::findOrFail($id);

        // Validar si hay condominios usándolo antes de eliminarlo
        $condosUsando = Condominio::where('plan_id', $plan->id)->count();
        if ($condosUsando > 0) {
            return redirect()->back()->with('error', "No se puede eliminar el plan '{$plan->nombre}' porque está siendo usado por {$condosUsando} condominio(s).");
        }

        $nombre = $plan->nombre;
        $plan->delete();

        // Registrar Auditoría
        AuditLog::create([
            'user_id' => Auth::id(),
            'accion' => 'eliminar',
            'modulo' => 'Plan',
            'detalle' => "Eliminó el plan de suscripción '{$nombre}' (ID: {$id}).",
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Plan eliminado exitosamente.');
    }

    /**
     * Crear banco (Master Admin)
     */
    public function storeBanco(Request $request)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:4',
            'activo' => 'required|boolean',
        ]);

        Banco::create($data);

        return redirect()->back()->with('success', 'Banco creado exitosamente.');
    }

    /**
     * Actualizar banco (Master Admin)
     */
    public function updateBanco(Request $request, $id)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $banco = Banco::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:4',
            'activo' => 'required|boolean',
        ]);

        $banco->update($data);

        return redirect()->back()->with('success', 'Banco actualizado exitosamente.');
    }

    /**
     * Eliminar banco (Master Admin)
     */
    public function destroyBanco($id)
    {
        if (!Auth::user()->esMaster()) {
            abort(403);
        }

        $banco = Banco::findOrFail($id);
        $banco->delete();

        return redirect()->back()->with('success', 'Banco eliminado exitosamente.');
    }
}
