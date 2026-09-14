<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ReporteController;

// Página Pública de Verificación de Autenticidad de Recibo de Pago (QR)
Route::get('/certificacion/pago/{payment}', [ReporteController::class, 'certificacionPago']);

// Página Pública de Verificación de Autenticidad de Nota de Crédito (QR)
Route::get('/certificacion/nota-credito/{creditNote}', [ReporteController::class, 'certificacionNotaCredito']);

// Página Pública de Validación Criptográfica de Recibo / Aviso de Cobro (Fase 3.1)
Route::get('/validar-recibo/{invoice}', [ReporteController::class, 'certificacionRecibo']);
Route::get('/certificacion/recibo/{invoice}', [ReporteController::class, 'certificacionRecibo']);

// Cualquier otra ruta web renderiza la vista contenedora de la SPA de Vue 3
Route::get('/{any}', function ($any = null) {
    return view('app');
})->where('any', '^(?!api|certificacion|validar-recibo).*$');
