<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\Apartamento;
use App\Models\Condominio;
use App\Models\CondominioConcepto;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();

        $query = Invoice::with(['apartamento.propietarios', 'payments', 'condominio', 'certificadoPor', 'reabiertoPor']);

        if ($user->esPropietario() && $user->apartamento_id) {
            $query->where('apartamento_id', $user->apartamento_id);
        } elseif ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('periodo')) {
            $query->where('periodo', $request->periodo);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        if ($request->has('estado_certificacion') && $request->estado_certificacion !== '') {
            $query->where('estado_certificacion', $request->estado_certificacion);
        }

        $perPage = (int)$request->get('per_page', 20);
        $invoices = $query->orderBy('fecha_emision', 'desc')->paginate($perPage);
        return $this->respuestaExitosa($invoices);
    }

    /**
     * Resumen de períodos de recibos con estado de certificación y control de reaperturas
     */
    public function periodosResumen(Request $request)
    {
        $user = auth()->user();
        $condominioId = $request->condominio_id ? (int)$request->condominio_id : $this->obtenerCondominioActual();

        $query = Invoice::query()->withoutGlobalScopes();

        if (!$user->esMaster() && $condominioId) {
            $query->where('condominio_id', $condominioId);
        } elseif ($request->has('condominio_id') && $request->condominio_id) {
            $query->where('condominio_id', $request->condominio_id);
        }

        $invoices = $query->with(['condominio', 'certificadoPor', 'reabiertoPor'])
                          ->orderBy('created_at', 'desc')
                          ->get();

        $grupos = $invoices->groupBy(function ($inv) {
            $tipo = $inv->tipo_recibo ?? 'ordinario';
            return $inv->condominio_id . '___' . $tipo . '___' . $inv->periodo;
        });

        $resumen = [];
        foreach ($grupos as $key => $items) {
            $first = $items->first();
            $totalBs = $items->sum('monto_total');
            $totalUsd = $items->sum('monto_total_usd');
            $vecesReabierto = (int)($first->veces_reabierto ?? 0);
            $estadoCertificacion = $first->estado_certificacion ?? 'certificado';

            $resumen[] = [
                'key' => $key,
                'condominio_id' => $first->condominio_id,
                'condominio_nombre' => $first->condominio?->nombre ?? 'Condominio #' . $first->condominio_id,
                'periodo' => $first->periodo,
                'tipo_recibo' => $first->tipo_recibo ?? 'ordinario',
                'titulo_proyecto' => $first->titulo_proyecto,
                'modalidad_calculo' => $first->modalidad_calculo ?? 'alicuota',
                'numero_recibo_general' => $first->numero_recibo_general,
                'total_apartamentos' => $items->count(),
                'total_monto_bs' => round($totalBs, 2),
                'total_monto_usd' => round($totalUsd, 2),
                'tasa_cambio' => $first->tasa_cambio,
                'estado_certificacion' => $estadoCertificacion,
                'fecha_emision' => $first->fecha_emision?->format('Y-m-d'),
                'fecha_certificacion' => $first->fecha_certificacion ? $first->fecha_certificacion->format('Y-m-d H:i') : null,
                'certificado_por' => $first->certificadoPor?->name,
                'veces_reabierto' => $vecesReabierto,
                'reaperturas_restantes' => max(0, 2 - $vecesReabierto),
                'motivo_reapertura' => $first->motivo_reapertura,
                'reabierto_por' => $first->reabiertoPor?->name,
                'puede_editar' => ($estadoCertificacion === 'borrador' || $user->esMaster()),
                'puede_reabrir' => ($user->esMaster() && $estadoCertificacion === 'certificado' && $vecesReabierto < 2),
                'detalles_gastos' => $first->detalles_gastos ?? [],
                'fondos' => $first->fondos ?? [],
            ];
        }

        return $this->respuestaExitosa($resumen);
    }

    public function store(Request $request)
    {
        $request->validate([
            'apartamento_id' => 'required|exists:apartamentos,id',
            'monto_total' => 'required|numeric|min:0',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'periodo' => 'required|string',
            'descripcion' => 'nullable|string',
        ]);

        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId);
        $fechaEmision = $request->fecha_emision ?? date('Y-m-d');
        
        $numReciboGeneral = Invoice::generarNumeroReciboGeneral($condominioId, $fechaEmision, $request->periodo);
        $numFactura = Invoice::generarSiguienteNumeroFactura($condominioId, $numReciboGeneral, $fechaEmision);

        $invoice = Invoice::create([
            'apartamento_id' => $request->apartamento_id,
            'condominio_id' => $condominioId,
            'numero_recibo_general' => $numReciboGeneral,
            'numero_factura' => $numFactura,
            'fecha_emision' => $request->fecha_emision,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'monto_total' => $request->monto_total,
            'tasa_cambio' => $condominio?->tasa_cambio ?? 36.50,
            'monto_pagado' => 0,
            'estado' => 'pendiente',
            'estado_certificacion' => 'borrador',
            'periodo' => $request->periodo,
            'descripcion' => $request->descripcion,
        ]);

        return $this->respuestaExitosa($invoice, 'Factura emitida exitosamente en estado borrador.', 201);
    }

    /**
     * Generación masiva de expensas congelando el histórico de gastos y alícuotas
     */
    public function generarMasivo(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string', // ej. "Julio del 2026" o "202608"
            'conceptos' => 'nullable|array',
            'dias_vencimiento' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string',
            'certificar_inmediatamente' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::findOrFail($condominioId);
        $apartamentos = Apartamento::with(['alicuotasAsignadas.condominioAlicuota'])
            ->where('condominio_id', $condominioId)
            ->get();
        $catalogoAlicuotas = \App\Models\CondominioAlicuota::where('condominio_id', $condominioId)->get();

        // Verificar si este período ya está certificado y no fue reabierto
        $invoicesExistentes = Invoice::where('condominio_id', $condominioId)
                                     ->where('periodo', $request->periodo)
                                     ->get();

        if ($invoicesExistentes->isNotEmpty()) {
            $primera = $invoicesExistentes->first();
            if ($primera->estaCertificado() && !$user->esMaster()) {
                return $this->respuestaError(
                    "El recibo para el período '{$request->periodo}' ya ha sido certificado y bloqueado. Para modificarlo, debes solicitar una reapertura al Super Administrador.",
                    403
                );
            }
        }

        // Obtener la estructura de gastos vigente para este período
        $conceptosVigentes = $request->conceptos;
        if (empty($conceptosVigentes)) {
            $dbConceptos = CondominioConcepto::where('condominio_id', $condominioId)->where('activo', true)->get();
            if ($dbConceptos->isNotEmpty()) {
                $conceptosVigentes = $dbConceptos->map(function ($c) {
                    return [
                        'ali' => $c->ali ?? '1',
                        'concepto' => $c->concepto,
                        'monto' => (float)$c->monto_base,
                    ];
                })->toArray();
            }
        }

        if ($request->filled('tasa_cambio') && (float)$request->tasa_cambio > 0) {
            $tasaBcv = (float)$request->tasa_cambio;
            if ($condominio) {
                $condominio->update(['tasa_cambio' => $tasaBcv]);
            }
        } else {
            $tasaBcv = (float)($condominio->tasa_cambio ?? 771.07);
        }
        $fondoReservaAcumulado = (float)($condominio->fondo_reserva_acumulado ?? 2997.06);
        $fondoReservaPorcentaje = (float)($condominio->fondo_reserva_porcentaje ?? 10.00);

        $diasVencimiento = $request->dias_vencimiento ?? 5;
        $fechaEmision = Carbon::now();
        $fechaVencimiento = Carbon::now()->addDays($diasVencimiento);
        $count = 0;

        $certificar = (bool)$request->certificar_inmediatamente;
        $estadoCert = $certificar ? 'certificado' : 'borrador';
        $fechaCert = $certificar ? Carbon::now() : null;
        $certPorId = $certificar ? $user->id : null;

        $vecesReabierto = $invoicesExistentes->isNotEmpty() ? (int)$invoicesExistentes->first()->veces_reabierto : 0;
        $numReciboGeneral = Invoice::generarNumeroReciboGeneral($condominioId, $fechaEmision, $request->periodo);
        $creditosAplicadosResumen = [];

        // Total presupuestado consolidado de gastos del edificio
        $totalPresupuestoGastosUsd = !empty($conceptosVigentes) ? (float)array_sum(array_column($conceptosVigentes, 'monto')) : 0.0;
        $fondoMesTotal = round($totalPresupuestoGastosUsd * ($fondoReservaPorcentaje / 100), 2);

        // PASO 1: Pre-cálculo de cuotas de cada apartamento
        $calculosAptos = [];
        foreach ($apartamentos as $apto) {
            $alicuota1Percent = $apto->getAlicuota(1);
            $detallesGastosSnapshot = [];
            $totalAlicuotaUsd = 0;

            if (!empty($conceptosVigentes)) {
                foreach ($conceptosVigentes as $g) {
                    $montoItem = (float)($g['monto'] ?? 0);
                    $aliId = $g['condominio_alicuota_id'] ?? $g['ali_id'] ?? null;
                    $aliGroup = strval($g['ali'] ?? '1');

                    $aliDef = null;
                    if ($aliId) {
                        $aliDef = $catalogoAlicuotas->firstWhere('id', (int)$aliId);
                    }
                    if (!$aliDef) {
                        $aliDef = $catalogoAlicuotas->first(function ($item) use ($aliGroup) {
                            return ($item->id == $aliGroup) || ($item->numero == $aliGroup);
                        });
                    }

                    $aliNumero = $aliDef ? $aliDef->numero : $aliGroup;
                    $aliNombre = $aliDef ? $aliDef->nombre : "Alícuota {$aliGroup}";
                    $aliIdentificador = $aliDef ? $aliDef->id : $aliGroup;

                    $porcentajeAplicado = $apto->getAlicuota($aliIdentificador);
                    $factorAplicado = $porcentajeAplicado / 100;

                    $conceptoTexto = trim($g['concepto'] ?? '');
                    if (!empty($g['comentario_adicional'])) {
                        $extra = trim($g['comentario_adicional'], "() \t\n\r\0\x0B");
                        if ($extra !== '' && !str_contains($conceptoTexto, "({$extra})")) {
                            $conceptoTexto .= " ({$extra})";
                        }
                    }

                    $montoAlicu = round($montoItem * $factorAplicado, 2);
                    $totalAlicuotaUsd += $montoAlicu;

                    $detallesGastosSnapshot[] = [
                        'condominio_alicuota_id' => $aliDef?->id,
                        'ali' => $aliNumero,
                        'alicuota_nombre' => $aliNombre,
                        'concepto' => $conceptoTexto,
                        'monto' => $montoItem,
                        'alicu' => $montoAlicu,
                        'alicuota_porcentaje' => $porcentajeAplicado,
                    ];
                }
            }

            $fondoMesAlicuota = round($fondoMesTotal * ($alicuota1Percent / 100), 2);
            $fondosSnapshot = [
                'nombre' => 'FONDO DE RESERVA',
                'acumulado' => $fondoReservaAcumulado,
                'monto_mes' => $fondoMesTotal,
                'monto_alicuota' => $fondoMesAlicuota,
            ];

            $calculosAptos[] = [
                'apto' => $apto,
                'totalAlicuotaUsd' => $totalAlicuotaUsd,
                'detallesGastosSnapshot' => $detallesGastosSnapshot,
                'fondoMesAlicuota' => $fondoMesAlicuota,
                'fondosSnapshot' => $fondosSnapshot,
            ];
        }

        // PASO 2: Mecanismo de compensación matemática automática al céntimo (+- 0.01 / 0.02)
        if (!empty($calculosAptos) && $totalPresupuestoGastosUsd > 0) {
            $sumCuotasDistribuidas = round(array_sum(array_column($calculosAptos, 'totalAlicuotaUsd')), 2);
            $diffCentimos = round($totalPresupuestoGastosUsd - $sumCuotasDistribuidas, 2);

            if (abs($diffCentimos) > 0 && abs($diffCentimos) <= 0.10) {
                $lastIdx = count($calculosAptos) - 1;
                $calculosAptos[$lastIdx]['totalAlicuotaUsd'] = round($calculosAptos[$lastIdx]['totalAlicuotaUsd'] + $diffCentimos, 2);
                $calculosAptos[$lastIdx]['detallesGastosSnapshot'][] = [
                    'condominio_alicuota_id' => null,
                    'ali' => 'COMP',
                    'alicuota_nombre' => 'Compensación de Redondeo',
                    'concepto' => 'Ajuste matemático de redondeo al céntimo (cuadre 100.0000%)',
                    'monto' => $diffCentimos,
                    'alicu' => $diffCentimos,
                    'alicuota_porcentaje' => 100.00,
                    'es_ajuste_redondeo' => true,
                ];
            }

            $sumFondosDistribuidos = round(array_sum(array_column($calculosAptos, 'fondoMesAlicuota')), 2);
            $diffFondos = round($fondoMesTotal - $sumFondosDistribuidos, 2);
            if (abs($diffFondos) > 0 && abs($diffFondos) <= 0.10) {
                $lastIdx = count($calculosAptos) - 1;
                $calculosAptos[$lastIdx]['fondoMesAlicuota'] = round($calculosAptos[$lastIdx]['fondoMesAlicuota'] + $diffFondos, 2);
                $calculosAptos[$lastIdx]['fondosSnapshot']['monto_alicuota'] = $calculosAptos[$lastIdx]['fondoMesAlicuota'];
                $calculosAptos[$lastIdx]['fondosSnapshot']['ajuste_redondeo_fondo'] = $diffFondos;
            }
        }

        // PASO 3: Guardar recibos definitivos con exactitud milimétrica
        foreach ($calculosAptos as $calc) {
            $apto = $calc['apto'];
            $totalAlicuotaUsd = $calc['totalAlicuotaUsd'];
            $detallesGastosSnapshot = $calc['detallesGastosSnapshot'];
            $fondoMesAlicuota = $calc['fondoMesAlicuota'];
            $fondosSnapshot = $calc['fondosSnapshot'];

            $totalPagarUsd = round($totalAlicuotaUsd + $fondoMesAlicuota, 2);
            $totalPagarBs = round($totalPagarUsd * $tasaBcv, 2);

            $invExistente = Invoice::withoutGlobalScopes()
                ->where('condominio_id', $condominioId)
                ->where('apartamento_id', $apto->id)
                ->where('periodo', $request->periodo)
                ->first();

            if ($invExistente && !empty($invExistente->numero_factura) && str_starts_with($invExistente->numero_factura, 'RI')) {
                $numFactura = $invExistente->numero_factura;
            } else {
                $numFactura = Invoice::generarSiguienteNumeroFactura($condominioId, $numReciboGeneral, $fechaEmision);
            }

            $invoice = Invoice::updateOrCreate(
                [
                    'condominio_id' => $condominioId,
                    'apartamento_id' => $apto->id,
                    'periodo' => $request->periodo,
                ],
                [
                    'numero_recibo_general' => $numReciboGeneral,
                    'numero_factura' => $numFactura,
                    'fecha_emision' => $fechaEmision,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total' => $totalPagarBs,
                    'monto_total_usd' => $totalPagarUsd,
                    'monto_alicuota_usd' => $totalAlicuotaUsd,
                    'tasa_cambio' => $tasaBcv,
                    'detalles_gastos' => $detallesGastosSnapshot,
                    'fondos' => $fondosSnapshot,
                    'estado' => 'pendiente',
                    'estado_certificacion' => $estadoCert,
                    'fecha_certificacion' => $fechaCert,
                    'certificado_por_id' => $certPorId,
                    'veces_reabierto' => $vecesReabierto,
                    'descripcion' => $request->descripcion ?? "Cuota de mantenimiento {$request->periodo}",
                ]
            );

            // Reconocimiento y aplicación automática de Notas de Crédito con saldo a favor
            $tienePagoCreditoPrevio = Payment::where('invoice_id', $invoice->id)
                ->where('metodo_pago', 'nota_credito')
                ->exists();

            if (!$tienePagoCreditoPrevio) {
                $notasDisponibles = CreditNote::where('apartamento_id', $apto->id)
                    ->whereIn('estado', ['disponible', 'parcial'])
                    ->where('monto_disponible', '>', 0.01)
                    ->orderBy('fecha_emision', 'asc')
                    ->get();

                if ($notasDisponibles->isNotEmpty()) {
                    $deudaPendienteRecibo = (float)$totalPagarBs;

                    foreach ($notasDisponibles as $nota) {
                        if ($deudaPendienteRecibo <= 0.01) break;

                        $montoADescontar = min((float)$nota->monto_disponible, $deudaPendienteRecibo);
                        $montoADescontarUsd = round($montoADescontar / $tasaBcv, 2);

                        // Crear notificación de pago pendiente por concepto de Nota de Crédito
                        $pagoCredito = Payment::create([
                            'invoice_id' => $invoice->id,
                            'condominio_id' => $condominioId,
                            'credit_note_id' => $nota->id,
                            'monto' => $montoADescontar,
                            'tasa_cambio' => $tasaBcv,
                            'fecha_pago' => $fechaEmision,
                            'metodo_pago' => 'nota_credito',
                            'referencia' => $nota->numero_nota_credito,
                            'banco' => 'Crédito a Favor (Nota de Crédito)',
                            'estado' => 'pendiente',
                            'observaciones' => "Aplicación automática de saldo de Nota de Crédito {$nota->numero_nota_credito} al recibo {$numFactura} ({$request->periodo}).",
                            'registrado_por' => $user->id,
                        ]);

                        $pagoCredito->invoices()->sync([
                            $invoice->id => ['monto_aplicado' => round($montoADescontar, 2)]
                        ]);

                        // Descontar saldo de la nota de crédito
                        $nota->descontarSaldo($montoADescontar, $montoADescontarUsd);

                        $deudaPendienteRecibo -= $montoADescontar;

                        $propPrincipal = $apto->propietarios->first();
                        $creditosAplicadosResumen[] = [
                            'apartamento_id' => $apto->id,
                            'apartamento_numero' => $apto->numero,
                            'propietario' => $propPrincipal ? $propPrincipal->nombre_completo : 'Propietario',
                            'numero_factura' => $numFactura,
                            'monto_recibo_bs' => $totalPagarBs,
                            'monto_recibo_usd' => $totalPagarUsd,
                            'numero_nota_credito' => $nota->numero_nota_credito,
                            'monto_aplicado_bs' => round($montoADescontar, 2),
                            'monto_aplicado_usd' => $montoADescontarUsd,
                            'saldo_restante_nc_bs' => round($nota->monto_disponible, 2),
                            'saldo_restante_nc_usd' => round($nota->monto_disponible_usd, 2),
                            'payment_id' => $pagoCredito->id,
                        ];
                    }
                }
            }

            $count++;
        }

        $mensaje = $certificar
            ? "Se han generado y certificado {$count} recibos para el período '{$request->periodo}'. El recibo queda bloqueado permanentemente."
            : "Se ha preparado el borrador de {$count} recibos para el período '{$request->periodo}'. Puedes previsualizarlo y certificarlo cuando estés seguro.";

        if (!empty($creditosAplicadosResumen)) {
            $totalCreditos = count($creditosAplicadosResumen);
            $mensaje .= " Se generaron automáticamente {$totalCreditos} notificaciones de pago por aplicación de Notas de Crédito.";
        }

        return $this->respuestaExitosa([
            'generadas' => $count,
            'estado_certificacion' => $estadoCert,
            'creditos_aplicados' => $creditosAplicadosResumen,
        ], $mensaje);
    }

    /**
     * Certificar y bloquear los recibos de un período mensual
     */
    public function certificarPeriodo(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string',
            'condominio_id' => 'nullable|exists:condominios,id',
        ]);

        $user = auth()->user();
        $condominioId = $request->condominio_id ? (int)$request->condominio_id : $this->obtenerCondominioActual();

        $invoices = Invoice::where('condominio_id', $condominioId)
                           ->where('periodo', $request->periodo)
                           ->get();

        if ($invoices->isEmpty()) {
            return $this->respuestaError("No se encontraron recibos registrados para el período '{$request->periodo}'.", 404);
        }

        Invoice::where('condominio_id', $condominioId)
               ->where('periodo', $request->periodo)
               ->update([
                   'estado_certificacion' => 'certificado',
                   'fecha_certificacion' => Carbon::now(),
                   'certificado_por_id' => $user->id,
               ]);

        // Registrar en auditoría
        AuditLog::create([
            'user_id' => $user->id,
            'accion' => 'certificar_recibo',
            'modulo' => 'contabilidad',
            'detalle' => "Certificó y bloqueó el recibo del período {$request->periodo} para el condominio #{$condominioId}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return $this->respuestaExitosa(
            ['periodo' => $request->periodo, 'estado_certificacion' => 'certificado'],
            "El recibo del período '{$request->periodo}' ha sido certificado y bloqueado con éxito. Ya no podrá ser modificado por el administrador."
        );
    }

    /**
     * Reabrir o devolver un recibo a estado borrador (Exclusivo Super Admin, Máximo 2 veces)
     */
    public function reabrirPeriodo(Request $request)
    {
        $user = auth()->user();

        if (!$user->esMaster()) {
            return $this->respuestaError('Solo el Super Administrador tiene autorización para desbloquear y reabrir recibos certificados.', 403);
        }

        $request->validate([
            'condominio_id' => 'required|exists:condominios,id',
            'periodo' => 'required|string',
            'motivo' => 'nullable|string|max:500',
        ]);

        $invoices = Invoice::where('condominio_id', $request->condominio_id)
                           ->where('periodo', $request->periodo)
                           ->get();

        if ($invoices->isEmpty()) {
            return $this->respuestaError("No se encontraron recibos para el condominio y período indicado.", 404);
        }

        $primera = $invoices->first();
        $vecesActuales = (int)($primera->veces_reabierto ?? 0);

        if ($vecesActuales >= 2) {
            return $this->respuestaError(
                "Este recibo ya alcanzó el límite máximo de 2 reaperturas permitidas y no puede volver a modificarse por políticas de seguridad contable.",
                422
            );
        }

        $nuevaCuenta = $vecesActuales + 1;
        $motivoTexto = $request->motivo ?: 'Reapertura autorizada por Super Admin para corrección de datos.';

        Invoice::where('condominio_id', $request->condominio_id)
               ->where('periodo', $request->periodo)
               ->update([
                   'estado_certificacion' => 'borrador',
                   'fecha_certificacion' => null,
                   'certificado_por_id' => null,
                   'veces_reabierto' => $nuevaCuenta,
                   'reabierto_por_id' => $user->id,
                   'motivo_reapertura' => $motivoTexto,
               ]);

        AuditLog::create([
            'user_id' => $user->id,
            'accion' => 'reabrir_recibo',
            'modulo' => 'contabilidad',
            'detalle' => "Super Admin reabrió el recibo {$request->periodo} del condominio #{$request->condominio_id} (Intento {$nuevaCuenta}/2). Motivo: {$motivoTexto}",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return $this->respuestaExitosa(
            [
                'periodo' => $request->periodo,
                'veces_reabierto' => $nuevaCuenta,
                'reaperturas_restantes' => max(0, 2 - $nuevaCuenta),
                'estado_certificacion' => 'borrador',
            ],
            "El recibo del período '{$request->periodo}' ha sido devuelto a estado Borrador con éxito. El administrador del condominio ahora puede editarlo. Reaperturas utilizadas: {$nuevaCuenta} de 2 permitidas."
        );
    }

    /**
     * Agregar un Gasto No Común (Cargo Particular) a una factura de apartamento específica
     */
    /**
     * Agregar un Gasto No Común (Cargo Particular) a una factura de apartamento específica
     */
    public function agregarGastoNoComun(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        if ($invoice->estaCertificado() && !$user->esMaster()) {
            return $this->respuestaError('Este recibo pertenece a un período certificado y bloqueado. No se pueden agregar gastos no comunes.', 403);
        }

        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto_usd' => 'required|numeric|min:0.01',
        ]);

        $gastos = $invoice->detalles_gastos;
        if (is_string($gastos)) {
            $gastos = json_decode($gastos, true) ?: [];
        }
        if (!is_array($gastos)) {
            $gastos = [];
        }

        $montoUsd = (float)$request->monto_usd;

        $nuevoGastoNoComun = [
            'ali' => 'no_comun',
            'alicuota_nombre' => 'Gasto No Común',
            'concepto' => strtoupper($request->concepto),
            'monto' => $montoUsd,
            'alicu' => $montoUsd,
            'alicuota_porcentaje' => 100.00,
            'es_no_comun' => true,
        ];

        $gastos[] = $nuevoGastoNoComun;

        // Recalcular montos de la factura
        $montoAlicuotaComun = 0;
        $montoNoComun = 0;

        foreach ($gastos as $g) {
            if (!empty($g['es_no_comun']) || ($g['ali'] ?? '') === 'no_comun') {
                $montoNoComun += (float)($g['alicu'] ?? ($g['monto'] ?? 0));
            } else {
                $montoAlicuotaComun += (float)($g['alicu'] ?? 0);
            }
        }

        $fondos = $invoice->fondos;
        if (is_string($fondos)) {
            $fondos = json_decode($fondos, true) ?: [];
        }
        $fondoAlicuota = (float)($fondos['monto_alicuota'] ?? 0);

        $totalUsd = round($montoAlicuotaComun + $fondoAlicuota + $montoNoComun, 2);
        $tasa = (float)($invoice->tasa_cambio > 0 ? $invoice->tasa_cambio : ($invoice->condominio->tasa_cambio ?? 36.50));
        $totalBs = round($totalUsd * $tasa, 2);

        $invoice->detalles_gastos = $gastos;
        $invoice->monto_alicuota_usd = round($montoAlicuotaComun, 2);
        $invoice->monto_total_usd = $totalUsd;
        $invoice->monto_total = $totalBs;
        $invoice->save();

        return $this->respuestaExitosa(
            $invoice->fresh(['apartamento.propietarios', 'condominio']),
            "Gasto no común '{$request->concepto}' agregado exitosamente al recibo del apartamento."
        );
    }

    /**
     * Eliminar un Gasto No Común de la factura de un apartamento
     */
    public function eliminarGastoNoComun(Request $request, Invoice $invoice, $index)
    {
        $user = auth()->user();
        if ($invoice->estaCertificado() && !$user->esMaster()) {
            return $this->respuestaError('Este recibo pertenece a un período certificado y bloqueado.', 403);
        }

        $gastos = $invoice->detalles_gastos;
        if (is_string($gastos)) {
            $gastos = json_decode($gastos, true) ?: [];
        }
        if (!is_array($gastos)) {
            $gastos = [];
        }

        $index = (int)$index;

        if (!isset($gastos[$index])) {
            return $this->respuestaError('El concepto especificado no existe en este recibo.', 404);
        }

        array_splice($gastos, $index, 1);

        $montoAlicuotaComun = 0;
        $montoNoComun = 0;

        foreach ($gastos as $g) {
            if (!empty($g['es_no_comun']) || ($g['ali'] ?? '') === 'no_comun') {
                $montoNoComun += (float)($g['alicu'] ?? ($g['monto'] ?? 0));
            } else {
                $montoAlicuotaComun += (float)($g['alicu'] ?? 0);
            }
        }

        $fondos = $invoice->fondos;
        if (is_string($fondos)) {
            $fondos = json_decode($fondos, true) ?: [];
        }
        $fondoAlicuota = (float)($fondos['monto_alicuota'] ?? 0);

        $totalUsd = round($montoAlicuotaComun + $fondoAlicuota + $montoNoComun, 2);
        $tasa = (float)($invoice->tasa_cambio > 0 ? $invoice->tasa_cambio : ($invoice->condominio->tasa_cambio ?? 36.50));
        $totalBs = round($totalUsd * $tasa, 2);

        $invoice->detalles_gastos = $gastos;
        $invoice->monto_alicuota_usd = round($montoAlicuotaComun, 2);
        $invoice->monto_total_usd = $totalUsd;
        $invoice->monto_total = $totalBs;
        $invoice->save();

        return $this->respuestaExitosa(
            $invoice->fresh(['apartamento.propietarios', 'condominio']),
            'Gasto no común eliminado del recibo.'
        );
    }

    public function show(Invoice $invoice)
    {
        return $this->respuestaExitosa($invoice->load(['apartamento.propietarios', 'payments.registradoPor', 'condominio', 'certificadoPor', 'reabiertoPor']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        if ($invoice->estaCertificado() && !$user->esMaster()) {
            return $this->respuestaError('Esta factura pertenece a un período certificado y bloqueado. No se puede modificar.', 403);
        }

        $invoice->update($request->all());
        return $this->respuestaExitosa($invoice, 'Factura actualizada exitosamente.');
    }

    public function destroy(Invoice $invoice)
    {
        $user = auth()->user();
        if ($invoice->estaCertificado() && !$user->esMaster()) {
            return $this->respuestaError('Esta factura pertenece a un período certificado y bloqueado. No se puede anular ni eliminar.', 403);
        }

        $invoice->delete();
        return $this->respuestaExitosa(null, 'Factura anulada lógicamente.');
    }

    /**
     * Envío masivo de recibos de cobro (RI y RG) por correo electrónico a los propietarios.
     */
    public function enviarRecibosPeriodo(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string',
            'condominio_id' => 'nullable|integer',
            'invoice_ids' => 'nullable|array',
        ]);

        $condominioId = $request->condominio_id ? (int)$request->condominio_id : $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId);
        if (!$condominio) {
            return $this->respuestaError('Condominio no encontrado.', 404);
        }

        $query = Invoice::withoutGlobalScopes()
            ->with(['apartamento.propietarios.user', 'condominio'])
            ->where('condominio_id', $condominioId)
            ->where('periodo', $request->periodo);

        if (!empty($request->invoice_ids)) {
            $query->whereIn('id', $request->invoice_ids);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return $this->respuestaError("No se encontraron recibos para el período '{$request->periodo}'.", 404);
        }

        // Obtener cuentas bancarias activas para incluir en el correo
        $cuentasBancarias = \App\Models\CondominioCuentaBancaria::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->where('activo', true)
            ->get();

        $enviados = 0;
        $destinatarios = [];
        $fallidos = 0;

        foreach ($invoices as $inv) {
            $apartamento = $inv->apartamento;
            if (!$apartamento) continue;

            $propietarios = $apartamento->propietarios;
            if ($propietarios->isEmpty()) continue;

            foreach ($propietarios as $prop) {
                $email = $prop->email ?: $prop->user?->email;
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $montoUsd = number_format((float)($inv->monto_total_usd ?: ($inv->monto_total / ($inv->tasa_cambio ?: 36.50))), 2, '.', '');
                $montoBs = number_format((float)$inv->monto_total, 2, ',', '.');
                $tasa = number_format((float)($inv->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50)), 2, ',', '.');
                $numeroApto = $apartamento->numero;
                $nombreProp = $prop->nombre_completo;
                $periodo = $inv->periodo;
                $numFactura = $inv->numero_factura ?: 'RI-' . $inv->id;
                $urlSistema = url('/login');
                $urlPdfRi = url("/api/v1/reportes/recibo-invoice/{$inv->id}");
                $urlPdfRg = url("/api/v1/reportes/recibo-general-periodo?condominio_id={$condominioId}&periodo=" . urlencode($periodo));

                // Construcción de la plantilla HTML para el correo
                $htmlBody = view('emails.recibo_cobro', compact(
                    'condominio',
                    'apartamento',
                    'prop',
                    'inv',
                    'cuentasBancarias',
                    'montoUsd',
                    'montoBs',
                    'tasa',
                    'periodo',
                    'urlSistema',
                    'urlPdfRi',
                    'urlPdfRg'
                ))->render();

                $asunto = "Recibo de Cobro {$periodo} • Apto. {$numeroApto} - {$condominio->nombre}";

                try {
                    \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($email, $nombreProp, $asunto, $condominio) {
                        $message->to($email, $nombreProp)
                                ->subject($asunto)
                                ->from(config('mail.from.address', 'notificaciones@azpro.com'), $condominio->nombre . ' - Sistema AZ PRO');
                    });

                    $enviados++;
                    $destinatarios[] = [
                        'apto' => $numeroApto,
                        'propietario' => $nombreProp,
                        'email' => $email,
                        'monto_usd' => $montoUsd,
                        'monto_bs' => $montoBs,
                    ];

                    // Registro en NotificacionHistorial
                    \App\Models\NotificacionHistorial::create([
                        'condominio_id' => $condominioId,
                        'user_id' => $prop->user_id,
                        'tipo' => 'email',
                        'plantilla' => 'recibo_cobro_periodo',
                        'destinatario' => $email,
                        'mensaje' => "Recibo de cobro período {$periodo} enviado a {$nombreProp} (Apto {$numeroApto}). Total: \${$montoUsd} USD (Bs. {$montoBs}).",
                        'estado' => 'enviado',
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Error enviando email de recibo a {$email}: " . $e->getMessage());
                    $fallidos++;
                }
            }
        }

        // Registrar en auditoría
        \App\Models\AuditLog::create([
            'user_id' => auth()->id() ?? 1,
            'accion' => 'envio_masivo_recibos_email',
            'modulo' => 'contabilidad',
            'detalle' => "Envío masivo de recibos por correo electrónico para el período {$request->periodo}. Total correos enviados: {$enviados}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return $this->respuestaExitosa([
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'total_inmuebles' => $invoices->count(),
            'destinatarios' => $destinatarios,
        ], "Se han enviado exitosamente {$enviados} recibos de cobro por correo electrónico a los propietarios.");
    }

    /**
     * Obtener la tasa oficial activa del sistema (fijada por Super Admin / Master).
     */
    protected function obtenerTasaActivaDelSistema($condominio = null): float
    {
        $tasaCentral = (float) (\App\Models\SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 0);

        if ($tasaCentral > 0.01) {
            return $tasaCentral;
        }

        if ($condominio && (float)$condominio->tasa_cambio > 0.01) {
            return (float)$condominio->tasa_cambio;
        }

        return 36.50;
    }

    /**
     * Simular cálculo de Cuota Extraordinaria a partes iguales con tasa activa del sistema.
     */
    public function simularCuotaExtraordinaria(Request $request)
    {
        $request->validate([
            'periodo' => 'nullable|string',
            'titulo_proyecto' => 'required|string|max:255',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.concepto' => 'required|string',
            'conceptos.*.monto' => 'required|numeric|min:0',
            'modalidad_calculo' => 'nullable|string|in:partes_iguales,monto_fijo',
            'monto_fijo_por_apartamento' => 'nullable|numeric|min:0',
            'apartamentos_ajustes' => 'nullable|array',
        ]);

        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::findOrFail($condominioId);
        $tasaBcv = $this->obtenerTasaActivaDelSistema($condominio);

        // Generar o validar identificador correlativo automático del sistema
        $periodo = trim($request->periodo ?? '');
        if ($periodo === '' || $periodo === 'EXT-AUTO') {
            $periodo = Invoice::generarSiguientePeriodoExtraordinario($condominioId);
        }

        $apartamentos = Apartamento::with('propietarios')
            ->where('condominio_id', $condominioId)
            ->orderBy('numero')
            ->get();

        $totalAptos = $apartamentos->count();
        if ($totalAptos === 0) {
            return $this->respuestaError("No hay apartamentos registrados en este condominio.", 422);
        }

        $totalProyectoUsd = 0;
        foreach ($request->conceptos as $c) {
            $totalProyectoUsd += (float)($c['monto'] ?? 0);
        }

        $modalidad = $request->modalidad_calculo ?? 'partes_iguales';
        $ajustesInput = collect($request->apartamentos_ajustes ?? [])->keyBy('apartamento_id');

        // Contar cuántos apartamentos no están expresamente exonerados
        $aptosParticipantes = 0;
        foreach ($apartamentos as $apto) {
            $ajuste = $ajustesInput->get($apto->id);
            if (!($ajuste && !empty($ajuste['exonerado']))) {
                $aptosParticipantes++;
            }
        }
        $divisorAptos = $aptosParticipantes > 0 ? $aptosParticipantes : $totalAptos;

        $cuotaBaseUsd = 0;
        if ($modalidad === 'monto_fijo') {
            $cuotaBaseUsd = (float)($request->monto_fijo_por_apartamento ?? 0);
        } else {
            $cuotaBaseUsd = round($totalProyectoUsd / $divisorAptos, 2);
        }

        $simulacionApartamentos = [];
        $totalRecaudarUsd = 0;
        $totalRecaudarBs = 0;

        foreach ($apartamentos as $apto) {
            $ajuste = $ajustesInput->get($apto->id);
            $esExonerado = (bool)($ajuste['exonerado'] ?? false);
            $motivo = $ajuste['motivo'] ?? ($esExonerado ? 'Exonerado por Asamblea' : '');
            
            if ($esExonerado) {
                $cuotaFinalUsd = 0.00;
            } elseif ($ajuste && isset($ajuste['monto_personalizado']) && is_numeric($ajuste['monto_personalizado'])) {
                $cuotaFinalUsd = (float)$ajuste['monto_personalizado'];
            } else {
                $cuotaFinalUsd = $cuotaBaseUsd;
            }

            $cuotaFinalBs = round($cuotaFinalUsd * $tasaBcv, 2);
            $totalRecaudarUsd += $cuotaFinalUsd;
            $totalRecaudarBs += $cuotaFinalBs;

            $prop = $apto->propietarios->first();

            // Desglose de conceptos proporcional para este apartamento
            $desgloseConceptos = [];
            foreach ($request->conceptos as $c) {
                $montoItemEdif = (float)$c['monto'];
                $cuotaItemApto = ($esExonerado || $divisorAptos === 0) ? 0.00 : round($montoItemEdif / $divisorAptos, 2);
                $desgloseConceptos[] = [
                    'concepto' => $c['concepto'],
                    'monto_edificio' => $montoItemEdif,
                    'cuota_apartamento' => $cuotaItemApto,
                ];
            }

            $simulacionApartamentos[] = [
                'apartamento_id' => $apto->id,
                'numero' => $apto->numero,
                'piso' => $apto->piso,
                'propietario_nombre' => $prop ? $prop->nombre_completo : 'Sin propietario asignado',
                'propietario_cedula' => $prop ? $prop->cedula : 'N/A',
                'propietario_email' => $prop ? $prop->email : '',
                'cuota_base_usd' => $cuotaBaseUsd,
                'cuota_final_usd' => $cuotaFinalUsd,
                'cuota_final_bs' => $cuotaFinalBs,
                'es_exonerado' => $esExonerado,
                'monto_personalizado' => $ajuste['monto_personalizado'] ?? null,
                'motivo_ajuste' => $motivo,
                'desglose_conceptos' => $desgloseConceptos,
            ];
        }

        return $this->respuestaExitosa([
            'periodo' => $periodo,
            'titulo_proyecto' => $request->titulo_proyecto,
            'tasa_cambio' => $tasaBcv,
            'modalidad_calculo' => $modalidad,
            'total_proyecto_usd' => round($totalProyectoUsd, 2),
            'total_recaudar_usd' => round($totalRecaudarUsd, 2),
            'total_recaudar_bs' => round($totalRecaudarBs, 2),
            'total_apartamentos' => $totalAptos,
            'apartamentos_participantes' => $aptosParticipantes,
            'cuota_base_calculada_usd' => $cuotaBaseUsd,
            'fondo_reserva_aplicado' => false,
            'apartamentos' => $simulacionApartamentos,
        ], 'Simulación de cuota extraordinaria calculada exitosamente.');
    }

    /**
     * Generar en lote los Recibos de Cuota Extraordinaria (Borrador o Certificado).
     */
    public function generarCuotaExtraordinaria(Request $request)
    {
        $request->validate([
            'periodo' => 'nullable|string',
            'titulo_proyecto' => 'required|string|max:255',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.concepto' => 'required|string',
            'conceptos.*.monto' => 'required|numeric|min:0',
            'dias_vencimiento' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string',
            'modalidad_calculo' => 'nullable|string|in:partes_iguales,monto_fijo',
            'monto_fijo_por_apartamento' => 'nullable|numeric|min:0',
            'apartamentos_ajustes' => 'nullable|array',
            'certificar_inmediatamente' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();
        $condominio = Condominio::findOrFail($condominioId);

        // La tasa BCV siempre se toma de la configuración activa del sistema (no editable por admin)
        $tasaBcv = $this->obtenerTasaActivaDelSistema($condominio);

        // Generar o validar identificador correlativo automático del sistema
        $periodo = trim($request->periodo ?? '');
        if ($periodo === '' || $periodo === 'EXT-AUTO') {
            $periodo = Invoice::generarSiguientePeriodoExtraordinario($condominioId);
        }

        // Verificar si este período extraordinario ya está certificado
        $invoicesExistentes = Invoice::where('condominio_id', $condominioId)
            ->where('periodo', $periodo)
            ->where('tipo_recibo', 'extraordinario')
            ->get();

        if ($invoicesExistentes->isNotEmpty()) {
            $primera = $invoicesExistentes->first();
            if ($primera->estaCertificado() && !$user->esMaster()) {
                return $this->respuestaError(
                    "La cuota extraordinaria para el período '{$periodo}' ya ha sido certificada y bloqueada. Para modificarla, debes solicitar una reapertura al Super Administrador.",
                    403
                );
            }
        }

        $apartamentos = Apartamento::with('propietarios')
            ->where('condominio_id', $condominioId)
            ->orderBy('numero')
            ->get();

        $totalAptos = $apartamentos->count();
        if ($totalAptos === 0) {
            return $this->respuestaError("No hay apartamentos registrados en este condominio.", 422);
        }

        $totalProyectoUsd = 0;
        foreach ($request->conceptos as $c) {
            $totalProyectoUsd += (float)($c['monto'] ?? 0);
        }

        $modalidad = $request->modalidad_calculo ?? 'partes_iguales';
        $ajustesInput = collect($request->apartamentos_ajustes ?? [])->keyBy('apartamento_id');

        // Contar cuántos apartamentos no están expresamente exonerados
        $aptosParticipantes = 0;
        foreach ($apartamentos as $apto) {
            $ajuste = $ajustesInput->get($apto->id);
            if (!($ajuste && !empty($ajuste['exonerado']))) {
                $aptosParticipantes++;
            }
        }
        $divisorAptos = $aptosParticipantes > 0 ? $aptosParticipantes : $totalAptos;

        $cuotaBaseUsd = ($modalidad === 'monto_fijo')
            ? (float)($request->monto_fijo_por_apartamento ?? 0)
            : round($totalProyectoUsd / $divisorAptos, 2);

        $diasVencimiento = $request->dias_vencimiento ?? 5;
        $fechaEmision = Carbon::now();
        $fechaVencimiento = Carbon::now()->addDays($diasVencimiento);

        $certificar = (bool)$request->certificar_inmediatamente;
        $estadoCert = $certificar ? 'certificado' : 'borrador';
        $fechaCert = $certificar ? Carbon::now() : null;
        $certPorId = $certificar ? $user->id : null;

        $vecesReabierto = $invoicesExistentes->isNotEmpty() ? (int)$invoicesExistentes->first()->veces_reabierto : 0;
        $numReciboGeneral = Invoice::generarNumeroReciboGeneral($condominioId, $fechaEmision, $periodo);

        $count = 0;
        $creditosAplicadosResumen = [];

        foreach ($apartamentos as $apto) {
            $ajuste = $ajustesInput->get($apto->id);
            $esExonerado = (bool)($ajuste['exonerado'] ?? false);
            $motivo = $ajuste['motivo'] ?? ($esExonerado ? 'Exonerado por Asamblea' : '');

            if ($esExonerado) {
                $cuotaFinalUsd = 0.00;
            } elseif ($ajuste && isset($ajuste['monto_personalizado']) && is_numeric($ajuste['monto_personalizado'])) {
                $cuotaFinalUsd = (float)$ajuste['monto_personalizado'];
            } else {
                $cuotaFinalUsd = $cuotaBaseUsd;
            }

            $totalPagarUsd = $cuotaFinalUsd;
            $totalPagarBs = round($totalPagarUsd * $tasaBcv, 2);

            // Desglose de conceptos de la cuota extraordinaria
            $detallesGastosSnapshot = [];
            foreach ($request->conceptos as $c) {
                $montoItemEdif = (float)$c['monto'];
                $cuotaItemApto = ($esExonerado || $divisorAptos === 0) ? 0.00 : round($montoItemEdif / $divisorAptos, 2);
                $conceptoTexto = trim($c['concepto']);
                if ($motivo !== '') {
                    $conceptoTexto .= " [{$motivo}]";
                }

                $detallesGastosSnapshot[] = [
                    'ali' => 'EXT',
                    'alicuota_nombre' => 'Cuota Extraordinaria (Partes Iguales)',
                    'concepto' => $conceptoTexto,
                    'monto' => $montoItemEdif,
                    'alicu' => $cuotaItemApto,
                    'es_extraordinario' => true,
                    'es_no_comun' => false,
                ];
            }

            // Exclusión estricta de Fondo de Reserva (0.00)
            $fondosSnapshot = [
                'nombre' => 'FONDO DE RESERVA',
                'acumulado' => (float)($condominio->fondo_reserva_acumulado ?? 0),
                'monto_mes' => 0.00,
                'monto_alicuota' => 0.00,
                'aplica' => false,
                'motivo' => 'No aplica para cuotas extraordinarias',
            ];

            $invExistente = Invoice::withoutGlobalScopes()
                ->where('condominio_id', $condominioId)
                ->where('apartamento_id', $apto->id)
                ->where('periodo', $periodo)
                ->where('tipo_recibo', 'extraordinario')
                ->first();

            if ($invExistente && !empty($invExistente->numero_factura)) {
                $numFactura = $invExistente->numero_factura;
            } else {
                $numFactura = Invoice::generarSiguienteNumeroFactura($condominioId, $numReciboGeneral, $fechaEmision);
            }

            $invoice = Invoice::updateOrCreate(
                [
                    'condominio_id' => $condominioId,
                    'apartamento_id' => $apto->id,
                    'periodo' => $periodo,
                    'tipo_recibo' => 'extraordinario',
                ],
                [
                    'numero_recibo_general' => $numReciboGeneral,
                    'numero_factura' => $numFactura,
                    'titulo_proyecto' => $request->titulo_proyecto,
                    'modalidad_calculo' => $modalidad,
                    'fecha_emision' => $fechaEmision,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total' => $totalPagarBs,
                    'monto_total_usd' => $totalPagarUsd,
                    'monto_alicuota_usd' => $totalPagarUsd,
                    'tasa_cambio' => $tasaBcv,
                    'detalles_gastos' => $detallesGastosSnapshot,
                    'fondos' => $fondosSnapshot,
                    'estado' => ($totalPagarUsd <= 0.001) ? 'pagado' : 'pendiente',
                    'monto_pagado' => 0,
                    'estado_certificacion' => $estadoCert,
                    'fecha_certificacion' => $fechaCert,
                    'certificado_por_id' => $certPorId,
                    'veces_reabierto' => $vecesReabierto,
                    'descripcion' => $request->descripcion ?? "Cuota Extraordinaria: {$request->titulo_proyecto} ({$request->periodo})",
                ]
            );

            // Reconocimiento y aplicación automática de Notas de Crédito si hay deuda pendiente
            if ($totalPagarBs > 0.01) {
                $tienePagoCreditoPrevio = Payment::where('invoice_id', $invoice->id)
                    ->where('metodo_pago', 'nota_credito')
                    ->exists();

                if (!$tienePagoCreditoPrevio) {
                    $notasDisponibles = CreditNote::where('apartamento_id', $apto->id)
                        ->whereIn('estado', ['disponible', 'parcial'])
                        ->where('monto_disponible', '>', 0.01)
                        ->orderBy('fecha_emision', 'asc')
                        ->get();

                    if ($notasDisponibles->isNotEmpty()) {
                        $deudaPendienteRecibo = (float)$totalPagarBs;

                        foreach ($notasDisponibles as $nota) {
                            if ($deudaPendienteRecibo <= 0.01) break;

                            $montoADescontar = min((float)$nota->monto_disponible, $deudaPendienteRecibo);
                            $montoADescontarUsd = round($montoADescontar / $tasaBcv, 2);

                            $pagoCredito = Payment::create([
                                'invoice_id' => $invoice->id,
                                'condominio_id' => $condominioId,
                                'credit_note_id' => $nota->id,
                                'monto' => $montoADescontar,
                                'tasa_cambio' => $tasaBcv,
                                'fecha_pago' => $fechaEmision,
                                'metodo_pago' => 'nota_credito',
                                'referencia' => $nota->numero_nota_credito,
                                'banco' => 'Crédito a Favor (Nota de Crédito)',
                                'estado' => 'pendiente',
                                'observaciones' => "Aplicación automática de saldo de Nota de Crédito {$nota->numero_nota_credito} a la cuota extraordinaria {$numFactura}.",
                                'registrado_por' => $user->id,
                            ]);

                            $pagoCredito->invoices()->sync([
                                $invoice->id => ['monto_aplicado' => round($montoADescontar, 2)]
                            ]);

                            $nota->descontarSaldo($montoADescontar, $montoADescontarUsd);
                            $deudaPendienteRecibo -= $montoADescontar;

                            $propPrincipal = $apto->propietarios->first();
                            $creditosAplicadosResumen[] = [
                                'apartamento_id' => $apto->id,
                                'apartamento_numero' => $apto->numero,
                                'propietario' => $propPrincipal ? $propPrincipal->nombre_completo : 'Propietario',
                                'numero_factura' => $numFactura,
                                'monto_recibo_bs' => $totalPagarBs,
                                'monto_recibo_usd' => $totalPagarUsd,
                                'numero_nota_credito' => $nota->numero_nota_credito,
                                'monto_aplicado_bs' => round($montoADescontar, 2),
                                'monto_aplicado_usd' => $montoADescontarUsd,
                                'payment_id' => $pagoCredito->id,
                            ];
                        }
                    }
                }
            }

            $count++;
        }

        // Registrar en Auditoría
        AuditLog::create([
            'user_id' => $user->id,
            'accion' => $certificar ? 'certificar_cuota_extraordinaria' : 'generar_borrador_cuota_extraordinaria',
            'modulo' => 'contabilidad',
            'detalle' => "Generó {$count} recibos de cuota extraordinaria para el período '{$request->periodo}' ({$request->titulo_proyecto}) en estado '{$estadoCert}'. Tasa BCV aplicada: {$tasaBcv}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        $mensaje = $certificar
            ? "Se han generado y certificado {$count} recibos de cuota extraordinaria para el proyecto '{$request->titulo_proyecto}'. Los montos han sido bloqueados."
            : "Se ha preparado el borrador de {$count} recibos de cuota extraordinaria para '{$request->titulo_proyecto}'. Puedes revisarlo y certificarlo cuando gustes.";

        return $this->respuestaExitosa([
            'generadas' => $count,
            'periodo' => $request->periodo,
            'titulo_proyecto' => $request->titulo_proyecto,
            'tasa_cambio' => $tasaBcv,
            'estado_certificacion' => $estadoCert,
            'creditos_aplicados' => $creditosAplicadosResumen,
        ], $mensaje, 201);
    }
}

