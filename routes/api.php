<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\PermissionController;
use App\Http\Controllers\Api\v1\MenuController;
use App\Http\Controllers\Api\v1\SelectOptionController;
use App\Http\Controllers\Api\v1\CondominioController;
use App\Http\Controllers\Api\v1\SaasBillingController;
use App\Http\Controllers\Api\v1\ApartamentoController;
use App\Http\Controllers\Api\v1\PropietarioController;
use App\Http\Controllers\Api\v1\InvoiceController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ExpenseController;
use App\Http\Controllers\Api\v1\ContabilidadController;
use App\Http\Controllers\Api\v1\VisitanteController;
use App\Http\Controllers\Api\v1\ReservaController;
use App\Http\Controllers\Api\v1\IncidenciaController;
use App\Http\Controllers\Api\v1\ComunicadoController;
use App\Http\Controllers\Api\v1\NotificacionController;
use App\Http\Controllers\Api\v1\AuditLogController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\ReporteController;
use App\Http\Controllers\Api\v1\ConceptoGastoController;
use App\Http\Controllers\Api\v1\CondominioAlicuotaController;
use App\Http\Controllers\Api\v1\CondominioCuentaController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\LandingController;
use App\Http\Controllers\Api\v1\PollController;

/*
|--------------------------------------------------------------------------
| API Routes v1 - Plataforma SaaS de Gestión de Condominios
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Autenticación pública, recuperación y contenido público de Landing Page
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verificar-2fa', [AuthController::class, 'verificar2FA']);
    Route::post('/recuperar-password', [AuthController::class, 'forgotPassword']);
    Route::get('/landing-content', [LandingController::class, 'index']);
    Route::get('/planes', [\App\Http\Controllers\Api\v1\PlanController::class, 'index']);

    // Rutas protegidas por Sanctum y auditadas
    Route::middleware(['auth:sanctum', 'audit'])->group(function () {
        // Planes SaaS (Solo Master para modificación)
        Route::apiResource('planes', \App\Http\Controllers\Api\v1\PlanController::class)->except(['index']);

        // Landing Page PWA - Edición Master
        Route::put('/landing-content', [LandingController::class, 'update']);
        
        // Usuario autenticado, cierre de sesión y seguridad (2FA)
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/auth/toggle-2fa', [AuthController::class, 'toggle2FA']);

        // Dashboards por rol
        Route::get('/dashboard/super-admin', [DashboardController::class, 'superAdmin']);
        Route::get('/dashboard/admin', [DashboardController::class, 'adminCondominio']);
        Route::get('/dashboard/propietario', [DashboardController::class, 'propietario']);

        // Configuración y listas dinámicas
        Route::get('/configuracion/tasa-cambio', [CondominioController::class, 'getTasaCambioCentral']);
        Route::post('/configuracion/tasa-cambio', [CondominioController::class, 'updateTasaCambioCentral']);
        Route::get('/configuracion/consultar-bcv', [CondominioController::class, 'consultarBcvEnVivo']);
        Route::apiResource('select-options', SelectOptionController::class);

        // Gestión y Administración Global de Usuarios (Super Admin y Admin)
        Route::apiResource('users', UserController::class);
        Route::put('/users/{user}/desbloquear', [UserController::class, 'desbloquear']);

        // Roles y Permisos (Super Admin)
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class)->only(['index', 'store']);

        // Menús dinámicos
        Route::apiResource('menus', MenuController::class);

        // Gestión de Condominios
        Route::apiResource('condominios', CondominioController::class);
        Route::post('/condominios/{condominio}/tasa-cambio', [CondominioController::class, 'updateTasaCambio']);
        Route::put('/condominios/{condominio}/asignar-administrador', [CondominioController::class, 'asignarAdministrador']);
        Route::get('/condominios/{condominio?}/alicuotas', [CondominioAlicuotaController::class, 'index']);
        Route::post('/condominios/{condominio?}/alicuotas', [CondominioAlicuotaController::class, 'store']);
        Route::apiResource('condominio-alicuotas', CondominioAlicuotaController::class);

        // Cuentas Bancarias, Pago Móvil y Bancos por Condominio
        Route::get('/bancos', [CondominioCuentaController::class, 'bancos']);
        Route::get('/condominios/{condominio?}/cuentas-bancarias', [CondominioCuentaController::class, 'index']);
        Route::post('/condominios/{condominio?}/cuentas-bancarias', [CondominioCuentaController::class, 'store']);
        Route::put('/cuentas-bancarias/{cuenta}', [CondominioCuentaController::class, 'update']);
        Route::delete('/cuentas-bancarias/{cuenta}', [CondominioCuentaController::class, 'destroy']);

        // Facturación SaaS (Cobros a Administradores)
        Route::get('/saas/invoices', [SaasBillingController::class, 'invoices']);
        Route::post('/saas/invoices', [SaasBillingController::class, 'generateInvoice']);
        Route::post('/saas/payments', [SaasBillingController::class, 'storePayment']);
        Route::get('/saas/summary', [SaasBillingController::class, 'financialSummary']);

        // Apartamentos y Propietarios
        Route::get('/apartamentos/plantilla-excel', [ApartamentoController::class, 'descargarPlantilla']);
        Route::post('/apartamentos/importar-excel', [ApartamentoController::class, 'importarExcel']);
        Route::post('/apartamentos/distribuir-equitativo', [ApartamentoController::class, 'distribuirEquitativo']);
        Route::post('/apartamentos/calcular-metraje', [ApartamentoController::class, 'calcularPorMetraje']);
        Route::apiResource('apartamentos', ApartamentoController::class);
        Route::apiResource('propietarios', PropietarioController::class);

        // Facturas y Expensas a Residentes
        Route::get('/invoices/periodos-resumen', [InvoiceController::class, 'periodosResumen']);
        Route::post('/invoices/generar-masivo', [InvoiceController::class, 'generarMasivo']);
        Route::post('/invoices/cuota-extraordinaria/simular', [InvoiceController::class, 'simularCuotaExtraordinaria']);
        Route::post('/invoices/cuota-extraordinaria/generar', [InvoiceController::class, 'generarCuotaExtraordinaria']);
        Route::post('/invoices/certificar-periodo', [InvoiceController::class, 'certificarPeriodo']);
        Route::post('/invoices/reabrir-periodo', [InvoiceController::class, 'reabrirPeriodo']);
        Route::post('/invoices/enviar-recibos-periodo', [InvoiceController::class, 'enviarRecibosPeriodo']);
        Route::post('/invoices/{invoice}/agregar-gasto-no-comun', [InvoiceController::class, 'agregarGastoNoComun']);
        Route::delete('/invoices/{invoice}/gasto-no-comun/{index}', [InvoiceController::class, 'eliminarGastoNoComun']);
        Route::apiResource('invoices', InvoiceController::class);

        // Pagos y Recibos
        Route::get('/credit-notes', [PaymentController::class, 'creditNotes']);
        Route::get('/apartamentos/{apartamento}/saldo-credito', [PaymentController::class, 'consultarSaldoCredito']);
        Route::apiResource('payments', PaymentController::class);
        Route::put('/payments/{payment}/aprobar', [PaymentController::class, 'aprobar']);
        Route::put('/payments/{payment}/rechazar', [PaymentController::class, 'rechazar']);
        Route::post('/payments/{payment}/reenviar-email', [PaymentController::class, 'reenviarEmailRecibo']);
        Route::post('/conciliacion/analizar', [PaymentController::class, 'analizarExtracto']);
        Route::post('/conciliacion/aprobar-lote', [PaymentController::class, 'aprobarLoteConciliado']);

        // Cuentas por Pagar, Conceptos de Gasto y Gastos del Condominio
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('conceptos-gasto', ConceptoGastoController::class);

        // Contabilidad Interna del Condominio (Libro Mayor, P&G, Cobranza, Flujo de Caja)
        Route::get('/contabilidad/libro-mayor', [ContabilidadController::class, 'libroMayor']);
        Route::get('/contabilidad/estado-resultados', [ContabilidadController::class, 'estadoResultados']);
        Route::get('/contabilidad/cuentas-por-cobrar', [ContabilidadController::class, 'cuentasPorCobrar']);
        Route::get('/contabilidad/flujo-caja', [ContabilidadController::class, 'flujoCaja']);

        // Control de Visitantes
        Route::apiResource('visitantes', VisitanteController::class);
        Route::put('/visitantes/{visitante}/salida', [VisitanteController::class, 'registrarSalida']);

        // Áreas Comunes y Reservas
        Route::apiResource('reservas', ReservaController::class);
        Route::put('/reservas/{reserva}/aprobar', [ReservaController::class, 'aprobar']);
        Route::put('/reservas/{reserva}/rechazar', [ReservaController::class, 'rechazar']);
        Route::put('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar']);
        Route::get('/areas-comunes', [ReservaController::class, 'areas']);
        Route::post('/areas-comunes', [ReservaController::class, 'storeArea']);
        Route::put('/areas-comunes/{area}', [ReservaController::class, 'updateArea']);
        Route::delete('/areas-comunes/{area}', [ReservaController::class, 'destroyArea']);

        // Incidencias y Cartelera de Comunicados
        Route::apiResource('incidencias', IncidenciaController::class);
        Route::apiResource('comunicados', ComunicadoController::class);

        // Notificaciones masivas y Cobranza Preventiva (Fase 4.1)
        Route::get('/notificaciones/historial', [NotificacionController::class, 'index']);
        Route::post('/notificaciones/recordatorios-cobro', [NotificacionController::class, 'enviarRecordatorioCobro']);
        Route::post('/notificaciones/cobranza-preventiva', [NotificacionController::class, 'ejecutarCobranzaPreventiva']);

        // Auditoría general y Copias de Seguridad (Fase 3.2)
        Route::get('/auditoria', [AuditLogController::class, 'index']);
        Route::get('/auditoria/backup/descargar', [AuditLogController::class, 'descargarBackup']);

        // Módulo de Asambleas y Votaciones Ponderadas por Alícuota (Fase 4.2)
        Route::get('/asambleas', [PollController::class, 'index']);
        Route::post('/asambleas', [PollController::class, 'store']);
        Route::get('/asambleas/{id}', [PollController::class, 'show']);
        Route::post('/asambleas/{id}/votar', [PollController::class, 'votar']);
        Route::post('/asambleas/{id}/finalizar', [PollController::class, 'finalizar']);
    });

    // Reportes en PDF (accesibles para descarga directa en nueva pestaña del navegador)
    Route::get('/reportes/recibo/{payment}', [ReporteController::class, 'reciboPagoPdf']);
    Route::get('/reportes/nota-credito/{creditNote}', [ReporteController::class, 'notaCreditoPdf']);
    Route::get('/reportes/recibo-invoice/{invoice}', [ReporteController::class, 'reciboInvoicePdf']);
    Route::get('/reportes/recibo-periodo', [ReporteController::class, 'reciboPeriodoPdf']);
    Route::get('/reportes/recibo-general', [ReporteController::class, 'reciboGeneralPdf']);
    Route::get('/reportes/recibos-lote-pdf', [ReporteController::class, 'recibosLotePdf']);
    Route::get('/reportes/morosos', [ReporteController::class, 'reporteMorososPdf']);
    Route::get('/reportes/visitantes', [ReporteController::class, 'reportesVisitantesPdf']);
    Route::get('/reportes/propietarios-pdf', [ReporteController::class, 'reportePropietariosPdf']);
    Route::get('/reportes/propietarios-excel', [ReporteController::class, 'reportePropietariosExcel']);
});

// Fallback para evitar RouteNotFoundException en solicitudes no autenticadas
Route::get('/login', function () {
    return response()->json(['success' => false, 'message' => 'No autenticado. Por favor inicie sesión.'], 401);
})->name('login');