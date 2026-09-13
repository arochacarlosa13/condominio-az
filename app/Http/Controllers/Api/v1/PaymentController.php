<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\CreditNote;
use App\Models\NotificacionHistorial;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();

        $query = Payment::with(['invoice.apartamento', 'invoices.apartamento', 'cuentaBancaria', 'creditNote', 'notaCreditoGenerada', 'registradoPor', 'condominio']);

        if ($user->esPropietario() && $user->apartamento_id) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('invoice', function ($sub) use ($user) {
                    $sub->where('apartamento_id', $user->apartamento_id);
                })->orWhereHas('invoices', function ($sub) use ($user) {
                    $sub->where('apartamento_id', $user->apartamento_id);
                });
            });
        } elseif ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        $payments = $query->orderBy('fecha_pago', 'desc')->paginate(20);
        return $this->respuestaExitosa($payments);
    }

    /**
     * Registro o notificación de pago (admite 1 o múltiples recibos de cobro).
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'cuenta_bancaria_id' => 'nullable|exists:condominio_cuentas_bancarias,id',
            'credit_note_id' => 'nullable|exists:credit_notes,id',
            'monto' => 'required|numeric|min:0.01',
            'moneda_origen' => 'nullable|string|in:VES,USD,EUR',
            'monto_divisa' => 'nullable|numeric',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|string',
            'referencia' => 'nullable|string',
            'banco' => 'nullable|string',
            'banco_origen' => 'nullable|string',
            'telefono_origen' => 'nullable|string',
            'cedula_origen' => 'nullable|string',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observaciones' => 'nullable|string',
            'aprobar_inmediato' => 'nullable|boolean',
        ]);

        $rawInvoiceIds = $request->invoice_ids ?: ($request->invoice_id ? [$request->invoice_id] : []);
        if (empty($rawInvoiceIds)) {
            return $this->respuestaError('Debe seleccionar al menos un recibo de cobro para notificar el pago.', 422);
        }

        $user = auth()->user();
        $invoices = Invoice::with('condominio')->whereIn('id', $rawInvoiceIds)->orderBy('fecha_emision', 'asc')->get();
        $firstInvoice = $invoices->first();
        $tasaCambio = (float)($firstInvoice->condominio->tasa_cambio ?? 36.50);

        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('comprobantes', 'public');
        }

        $monedaOrigen = $request->moneda_origen ?: 'VES';
        $tasaCambio = (float)($firstInvoice->condominio->tasa_cambio ?? 36.50);

        if ($monedaOrigen === 'USD' || in_array($request->metodo_pago, ['efectivo_usd', 'zelle'])) {
            $monedaOrigen = 'USD';
            $montoDivisa = round((float)($request->monto_divisa ?: ($request->monto / $tasaCambio)), 2);
            // Blindaje estricto: El monto en Bolívares se calcula de forma inalterable a la tasa BCV oficial
            $montoBs = round($montoDivisa * $tasaCambio, 2);
        } else {
            $monedaOrigen = 'VES';
            $montoBs = round((float)$request->monto, 2);
            $montoDivisa = round($montoBs / $tasaCambio, 2);
        }

        $payment = Payment::create([
            'invoice_id' => $firstInvoice->id,
            'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
            'credit_note_id' => $request->credit_note_id,
            'condominio_id' => $firstInvoice->condominio_id,
            'numero_recibo_pago' => null,
            'monto' => $montoBs,
            'moneda_origen' => $monedaOrigen,
            'monto_divisa' => $montoDivisa,
            'tasa_cambio' => $tasaCambio,
            'fecha_pago' => $request->fecha_pago,
            'metodo_pago' => $request->metodo_pago,
            'referencia' => $request->referencia,
            'banco' => $request->banco,
            'banco_origen' => $request->banco_origen,
            'telefono_origen' => $request->telefono_origen,
            'cedula_origen' => $request->cedula_origen,
            'estado' => 'pendiente',
            'comprobante_path' => $comprobantePath,
            'observaciones' => $request->observaciones,
            'registrado_por' => $user->id,
        ]);

        // Distribuir el monto transferido entre los recibos seleccionados
        $montoRestante = $montoBs;
        $syncData = [];

        foreach ($invoices as $inv) {
            if ($montoRestante <= 0) break;
            
            $totalInvBs = (float)($inv->monto_total_bs_efectivo > 0 ? $inv->monto_total_bs_efectivo : $inv->monto_total);
            $saldoBs = max(0, $totalInvBs - (float)$inv->monto_pagado);
            $saldoARecuperar = $saldoBs > 0 ? $saldoBs : $totalInvBs;

            $aplicar = min($montoRestante, $saldoARecuperar);

            if ($aplicar > 0) {
                $syncData[$inv->id] = ['monto_aplicado' => round($aplicar, 2)];
                $montoRestante -= $aplicar;
            }
        }

        $payment->invoices()->sync($syncData);

        // Si es administrador o supervisor y solicitó aprobación inmediata (ej. efectivo en mano o ya conciliado)
        if ($request->boolean('aprobar_inmediato') && ($user->esMaster() || $user->esAdmin() || $user->esSupervisor())) {
            return $this->aprobar($request, $payment);
        }

        $mensaje = 'Pago registrado exitosamente. En espera de verificación bancaria y aprobación por administración.';
        return $this->respuestaExitosa($payment->load(['invoice.apartamento', 'invoices.apartamento', 'cuentaBancaria', 'registradoPor']), $mensaje, 201);
    }

    /**
     * Aprobación de pago notificado (Supervisor o Admin).
     * Si el monto supera la deuda de los recibos, genera automáticamente una Nota de Crédito y notifica por email.
     */
    public function aprobar(Request $request, Payment $payment)
    {
        if ($payment->estado === 'aprobado') {
            return $this->respuestaError('Este pago ya ha sido verificado y aprobado previamente.', 400);
        }

        $numReciboPago = $payment->numero_recibo_pago ?: Payment::generarNumeroReciboPago($payment->condominio_id, $payment->fecha_pago);

        $payment->update([
            'estado' => 'aprobado',
            'numero_recibo_pago' => $numReciboPago,
        ]);

        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();

        // 1. Calcular deuda real pendiente de los recibos cubiertos antes de este pago
        $totalDeudaRequerida = 0;
        foreach ($coveredInvoices as $inv) {
            $totalFactura = (float)($inv->monto_total_bs_efectivo > 0 ? $inv->monto_total_bs_efectivo : $inv->monto_total);
            $otrosPagosAprobados = (float)$inv->payments()
                ->where('estado', 'aprobado')
                ->where('payments.id', '!=', $payment->id)
                ->sum('monto');
            $saldoRealPendiente = max(0, $totalFactura - $otrosPagosAprobados);
            $totalDeudaRequerida += $saldoRealPendiente;
        }

        // 2. Recalcular saldos de todas las facturas cubiertas
        foreach ($coveredInvoices as $inv) {
            $inv->recalcularMontoPagadoYEstado();
        }

        // 3. Verificar si existe excedente a favor para generar Nota de Crédito
        $montoPago = (float)$payment->monto;
        $notaCreditoGenerada = null;

        if ($montoPago > ($totalDeudaRequerida + 0.01)) {
            $excedenteBs = round($montoPago - $totalDeudaRequerida, 2);
            $condominio = $payment->condominio ?: Condominio::find($payment->condominio_id);
            $tasa = (float)($payment->tasa_cambio ?: ($condominio?->tasa_cambio ?: 36.50));
            $excedenteUsd = round($excedenteBs / $tasa, 2);

            $primerRecibo = $coveredInvoices->first();
            $apto = $payment->invoice?->apartamento ?: $primerRecibo?->apartamento;
            if (!$apto && $primerRecibo) {
                $apto = Apartamento::with('propietarios')->find($primerRecibo->apartamento_id);
            }

            $numNC = CreditNote::generarNumeroNotaCredito($payment->condominio_id, $payment->fecha_pago);

            $notaCreditoGenerada = CreditNote::create([
                'condominio_id' => $payment->condominio_id,
                'apartamento_id' => $apto?->id,
                'payment_id' => $payment->id,
                'numero_nota_credito' => $numNC,
                'fecha_emision' => $payment->fecha_pago ?? Carbon::now(),
                'monto_original' => $excedenteBs,
                'monto_original_usd' => $excedenteUsd,
                'monto_disponible' => $excedenteBs,
                'monto_disponible_usd' => $excedenteUsd,
                'tasa_cambio' => $tasa,
                'estado' => 'disponible',
                'motivo' => "Excedente de pago aprobado en Recibo {$numReciboPago}",
                'observaciones' => "Generado automáticamente tras cubrir la deuda de {$coveredInvoices->count()} recibo(s).",
            ]);

            // Enviar notificación por correo electrónico al propietario
            $prop = $apto?->propietarios()->where('es_propietario_principal', true)->first() ?: $apto?->propietarios()->first();
            if ($prop && !empty($prop->email) && filter_var($prop->email, FILTER_VALIDATE_EMAIL)) {
                $email = $prop->email;
                $nombreProp = $prop->nombre_completo;
                $numeroApto = $apto->numero;
                $montoUsdFormatted = number_format($excedenteUsd, 2, '.', '');
                $montoBsFormatted = number_format($excedenteBs, 2, ',', '.');
                $tasaFormatted = number_format($tasa, 2, ',', '.');
                $urlSistema = url('/login');

                try {
                    $htmlBody = view('emails.nota_credito', [
                        'condominio' => $condominio,
                        'nombreProp' => $nombreProp,
                        'numeroApto' => $numeroApto,
                        'notaCredito' => $notaCreditoGenerada,
                        'reciboPagoNumero' => $numReciboPago,
                        'montoUsd' => $montoUsdFormatted,
                        'montoBs' => $montoBsFormatted,
                        'tasa' => $tasaFormatted,
                        'urlSistema' => $urlSistema,
                    ])->render();

                    $asunto = "Nota de Crédito a su Favor ({$numNC}) • Apto. {$numeroApto} - {$condominio->nombre}";

                    Mail::html($htmlBody, function ($message) use ($email, $nombreProp, $asunto, $condominio) {
                        $message->to($email, $nombreProp)
                                ->subject($asunto)
                                ->from(config('mail.from.address', 'notificaciones@azpro.com'), $condominio->nombre . ' - Sistema AZ PRO');
                    });

                    NotificacionHistorial::create([
                        'condominio_id' => $payment->condominio_id,
                        'user_id' => $prop->user_id,
                        'tipo' => 'email',
                        'plantilla' => 'nota_credito_excedente',
                        'destinatario' => $email,
                        'mensaje' => "Nota de crédito {$numNC} generada por excedente de \${$montoUsdFormatted} USD (Bs. {$montoBsFormatted}) para {$nombreProp} (Apto {$numeroApto}).",
                        'estado' => 'enviado',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Error enviando email de nota de crédito a {$email}: " . $e->getMessage());
                }
            }

            // Registrar en auditoría
            AuditLog::create([
                'user_id' => auth()->id() ?? 1,
                'accion' => 'generacion_nota_credito_excedente',
                'modulo' => 'pagos',
                'detalle' => "Emisión automática de Nota de Crédito {$numNC} por excedente de Bs. {$excedenteBs} (\${$excedenteUsd} USD) al aprobar pago {$numReciboPago}.",
                'ip_address' => $request->ip() ?? '127.0.0.1',
            ]);
        }

        // 4. Enviar comprobante oficial de pago (Recibo RP) por correo automáticamente al propietario
        $emailRes = $this->enviarEmailReciboPago($payment);

        $mensaje = "Pago verificado y aprobado exitosamente. Emitido Recibo N° {$numReciboPago}.";
        if ($emailRes['success']) {
            $mensaje .= " Se envió el comprobante al correo {$emailRes['destinatario']}.";
        }
        if ($notaCreditoGenerada) {
            $mensaje .= " Se generó automáticamente la Nota de Crédito {$notaCreditoGenerada->numero_nota_credito} por excedente de Bs. " . number_format($notaCreditoGenerada->monto_original, 2, ',', '.') . " a favor del propietario.";
        }

        return $this->respuestaExitosa([
            'pago' => $payment->fresh(['invoice', 'invoices', 'cuentaBancaria']),
            'nota_credito' => $notaCreditoGenerada,
            'email_enviado' => $emailRes['success'],
        ], $mensaje);
    }

    /**
     * Enviar comprobante oficial de pago (Recibo RP) por correo al propietario.
     */
    public function enviarEmailReciboPago(Payment $payment): array
    {
        $payment->loadMissing(['condominio', 'invoice.apartamento.propietarios.user', 'invoices.apartamento.propietarios.user', 'cuentaBancaria', 'notaCreditoGenerada']);

        $condominio = $payment->condominio ?: Condominio::find($payment->condominio_id);
        if (!$condominio) {
            return ['success' => false, 'message' => 'Condominio no encontrado.'];
        }

        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();
        $primerRecibo = $coveredInvoices->first();
        $apartamento = $payment->invoice?->apartamento ?: $primerRecibo?->apartamento;
        if (!$apartamento && $primerRecibo) {
            $apartamento = Apartamento::with(['propietarios.user'])->find($primerRecibo->apartamento_id);
        }

        if (!$apartamento) {
            return ['success' => false, 'message' => 'Inmueble / Apartamento no encontrado.'];
        }

        $prop = $apartamento->propietarios()->where('es_propietario_principal', true)->first() ?: $apartamento->propietarios()->first();
        $email = $prop?->email ?: $prop?->user?->email;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => "El propietario del Apto. {$apartamento->numero} no tiene un correo electrónico válido configurado."];
        }

        $tasa = (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50));
        $montoBs = number_format((float)$payment->monto, 2, ',', '.');
        $montoUsd = number_format((float)($payment->monto_divisa ?: round($payment->monto / $tasa, 2)), 2, '.', '');
        $tasaFormatted = number_format($tasa, 2, ',', '.');
        $numReciboPago = $payment->numero_recibo_pago ?: 'RP-' . $payment->id;
        $nombreProp = $prop->nombre_completo;
        $numeroApto = $apartamento->numero;
        $urlSistema = url('/login');
        $urlPdfRp = url("/api/v1/reportes/recibo/{$payment->id}");

        // Desglose de facturas cubiertas
        $recibosCubiertos = [];
        foreach ($coveredInvoices as $inv) {
            $montoAplicado = $inv->pivot ? (float)$inv->pivot->monto_aplicado : (float)$inv->monto_total;
            $recibosCubiertos[] = [
                'periodo' => $inv->periodo,
                'numero' => $inv->numero_factura ?: 'RI-' . $inv->id,
                'monto_bs' => number_format($montoAplicado, 2, ',', '.'),
            ];
        }

        // Datos del canal receptor
        $canalDestino = null;
        if ($payment->cuentaBancaria) {
            $cb = $payment->cuentaBancaria;
            $canalDestino = "{$cb->banco_nombre}" . ($cb->numero_cuenta ? " - Cta: {$cb->numero_cuenta}" : '') . ($cb->telefono_pago_movil ? " - Pago Móvil: {$cb->telefono_pago_movil}" : '');
        } elseif ($payment->condominio) {
            $canalDestino = $payment->condominio->banco_nombre ?? 'Caja / Banco Principal';
        }

        // Datos de nota de crédito si aplica
        $notaCreditoInfo = null;
        $nc = $payment->notaCreditoGenerada ?: $payment->creditNotes()->first();
        if ($nc) {
            $notaCreditoInfo = [
                'numero' => $nc->numero_nota_credito,
                'monto_bs' => number_format((float)$nc->monto_original, 2, ',', '.'),
                'monto_usd' => number_format((float)$nc->monto_original_usd, 2, '.', ''),
            ];
        }

        $htmlBody = view('emails.recibo_pago', [
            'condominio' => $condominio,
            'nombreProp' => $nombreProp,
            'numeroApto' => $numeroApto,
            'numReciboPago' => $numReciboPago,
            'montoBs' => $montoBs,
            'montoUsd' => $montoUsd,
            'tasa' => $tasaFormatted,
            'metodoPago' => $payment->metodo_pago,
            'bancoEmisor' => $payment->banco_origen ?: $payment->banco,
            'referencia' => $payment->referencia,
            'fechaPago' => Carbon::parse($payment->fecha_pago)->format('d/m/Y'),
            'canalDestino' => $canalDestino,
            'recibosCubiertos' => $recibosCubiertos,
            'notaCredito' => $notaCreditoInfo,
            'urlSistema' => $urlSistema,
            'urlPdfRp' => $urlPdfRp,
        ])->render();

        $asunto = "Comprobante Oficial de Pago {$numReciboPago} • Apto. {$numeroApto} - {$condominio->nombre}";

        try {
            Mail::html($htmlBody, function ($message) use ($email, $nombreProp, $asunto, $condominio) {
                $message->to($email, $nombreProp)
                        ->subject($asunto)
                        ->from(config('mail.from.address', 'notificaciones@azpro.com'), $condominio->nombre . ' - Sistema AZ PRO');
            });

            NotificacionHistorial::create([
                'condominio_id' => $payment->condominio_id,
                'user_id' => $prop->user_id,
                'tipo' => 'email',
                'plantilla' => 'recibo_pago_oficial',
                'destinatario' => $email,
                'mensaje' => "Recibo oficial de pago {$numReciboPago} enviado a {$nombreProp} (Apto {$numeroApto}). Total: Bs. {$montoBs} (\${$montoUsd} USD).",
                'estado' => 'enviado',
            ]);

            return [
                'success' => true,
                'destinatario' => $email,
                'message' => "Recibo {$numReciboPago} enviado exitosamente al correo {$email}.",
            ];
        } catch (\Throwable $e) {
            Log::warning("Error enviando email de recibo de pago a {$email}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error al enviar correo a {$email}: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Reenviar comprobante oficial de pago (Recibo RP) por correo a solicitud del administrador.
     */
    public function reenviarEmailRecibo(Request $request, Payment $payment)
    {
        if ($payment->estado !== 'aprobado') {
            return $this->respuestaError('El recibo solo puede ser enviado por correo una vez que el pago esté aprobado y conciliado.', 400);
        }

        $res = $this->enviarEmailReciboPago($payment);

        if (!$res['success']) {
            return $this->respuestaError($res['message'], 422);
        }

        return $this->respuestaExitosa(null, $res['message']);
    }

    /**
     * Rechazo de pago con justificación obligatoria (mínimo 40 caracteres).
     */
    public function rechazar(Request $request, Payment $payment)
    {
        $request->validate([
            'motivo' => 'required|string|min:40',
        ], [
            'motivo.min' => 'La justificación del rechazo debe tener un mínimo de 40 caracteres para explicar claramente la razón al propietario.',
            'motivo.required' => 'Es obligatorio ingresar la justificación del rechazo.',
        ]);

        $payment->update([
            'estado' => 'rechazado',
            'motivo_rechazo' => $request->motivo,
        ]);

        // Si este pago fue originado a partir de una nota de crédito, restaurar su saldo
        if ($payment->credit_note_id && $payment->creditNote) {
            $tasa = (float)($payment->tasa_cambio ?: 36.50);
            $payment->creditNote->restaurarSaldo($payment->monto, $payment->monto / $tasa);
        }

        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();
        foreach ($coveredInvoices as $inv) {
            $inv->recalcularMontoPagadoYEstado();
        }

        return $this->respuestaExitosa($payment, 'Pago rechazado. Se ha notificado la justificación para que el propietario pueda realizar las correcciones necesarias.');
    }

    /**
     * Actualizar registro de pago y recalcular saldos de facturas cubiertas.
     */
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'exists:invoices,id',
            'cuenta_bancaria_id' => 'nullable|exists:condominio_cuentas_bancarias,id',
            'monto' => 'required|numeric|min:0.01',
            'moneda_origen' => 'nullable|string|in:VES,USD,EUR',
            'monto_divisa' => 'nullable|numeric',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|string',
            'referencia' => 'nullable|string',
            'banco' => 'nullable|string',
            'banco_origen' => 'nullable|string',
            'telefono_origen' => 'nullable|string',
            'cedula_origen' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $tasaCambio = (float)($payment->tasa_cambio ?: ($payment->condominio?->tasa_cambio ?: 36.50));
        $monedaOrigen = $request->moneda_origen ?: 'VES';

        if ($monedaOrigen === 'USD' || in_array($request->metodo_pago, ['efectivo_usd', 'zelle'])) {
            $monedaOrigen = 'USD';
            $montoDivisa = round((float)($request->monto_divisa ?: ($request->monto / $tasaCambio)), 2);
            $montoBs = round($montoDivisa * $tasaCambio, 2);
        } else {
            $monedaOrigen = 'VES';
            $montoBs = round((float)$request->monto, 2);
            $montoDivisa = round($montoBs / $tasaCambio, 2);
        }

        $paymentData = [
            'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
            'monto' => $montoBs,
            'moneda_origen' => $monedaOrigen,
            'monto_divisa' => $montoDivisa,
            'fecha_pago' => $request->fecha_pago,
            'metodo_pago' => $request->metodo_pago,
            'referencia' => $request->referencia,
            'banco' => $request->banco,
            'banco_origen' => $request->banco_origen,
            'telefono_origen' => $request->telefono_origen,
            'cedula_origen' => $request->cedula_origen,
            'observaciones' => $request->observaciones,
        ];

        if ($payment->estado !== 'aprobado') {
            $paymentData['motivo_rechazo'] = null;
            $paymentData['estado'] = 'pendiente';
        }

        $payment->update($paymentData);

        // Re-sincronizar distribución si se enviaron invoice_ids
        if (!empty($request->invoice_ids)) {
            $invoices = Invoice::whereIn('id', $request->invoice_ids)->orderBy('fecha_emision', 'asc')->get();
            $montoRestante = (float)$request->monto;
            $syncData = [];

            foreach ($invoices as $inv) {
                if ($montoRestante <= 0) break;
                
                $totalInvBs = (float)($inv->monto_total_bs_efectivo > 0 ? $inv->monto_total_bs_efectivo : $inv->monto_total);
                $saldoBs = max(0, $totalInvBs - (float)$inv->monto_pagado);
                $saldoARecuperar = $saldoBs > 0 ? $saldoBs : $totalInvBs;

                $aplicar = min($montoRestante, $saldoARecuperar);

                if ($aplicar > 0) {
                    $syncData[$inv->id] = ['monto_aplicado' => round($aplicar, 2)];
                    $montoRestante -= $aplicar;
                }
            }
            $payment->invoices()->sync($syncData);
        }

        // Recalcular facturas
        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();
        foreach ($coveredInvoices as $inv) {
            $inv->recalcularMontoPagadoYEstado();
        }

        return $this->respuestaExitosa($payment->load(['invoice', 'invoices', 'cuentaBancaria', 'registradoPor']), 'Registro de pago actualizado y saldos recalculados correctamente.');
    }

    /**
     * Listado de Notas de Crédito con filtros por condominio y apartamento
     */
    public function creditNotes(Request $request)
    {
        $user = auth()->user();
        $condominioId = $this->obtenerCondominioActual();

        $query = CreditNote::with(['apartamento.propietarios', 'origenPago', 'pagosAplicados', 'condominio']);

        if ($user->esPropietario() && $user->apartamento_id) {
            $query->where('apartamento_id', $user->apartamento_id);
        } elseif ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('apartamento_id') && $request->apartamento_id) {
            $query->where('apartamento_id', $request->apartamento_id);
        }

        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $perPage = (int)$request->get('per_page', 20);
        $creditNotes = $query->orderBy('fecha_emision', 'desc')->paginate($perPage);

        return $this->respuestaExitosa($creditNotes);
    }

    /**
     * Consultar saldo disponible a favor por Notas de Crédito de un apartamento
     */
    public function consultarSaldoCredito(Apartamento $apartamento)
    {
        $notas = CreditNote::where('apartamento_id', $apartamento->id)
            ->whereIn('estado', ['disponible', 'parcial'])
            ->where('monto_disponible', '>', 0)
            ->get();

        $totalBs = $notas->sum('monto_disponible');
        $totalUsd = $notas->sum('monto_disponible_usd');

        return $this->respuestaExitosa([
            'apartamento_id' => $apartamento->id,
            'apartamento_numero' => $apartamento->numero,
            'saldo_disponible_bs' => round($totalBs, 2),
            'saldo_disponible_usd' => round($totalUsd, 2),
            'notas_credito' => $notas,
        ]);
    }

    public function show(Payment $payment)
    {
        return $this->respuestaExitosa($payment->load(['invoice.apartamento.propietarios', 'invoices.apartamento', 'cuentaBancaria', 'creditNote', 'notaCreditoGenerada', 'registradoPor', 'condominio']));
    }

    public function destroy(Payment $payment)
    {
        $user = auth()->user();
        if ($payment->estado === 'aprobado' && !$user->esMaster() && !$user->esAdmin()) {
            return $this->respuestaError('Este pago ha sido verificado y certificado.', 403);
        }

        // Si el pago proviene de una nota de crédito, restaurar su saldo
        if ($payment->credit_note_id && $payment->creditNote) {
            $tasa = (float)($payment->tasa_cambio ?: 36.50);
            $payment->creditNote->restaurarSaldo($payment->monto, $payment->monto / $tasa);
        }

        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices->all() : collect([$payment->invoice])->filter()->all();
        $payment->delete();

        foreach ($coveredInvoices as $inv) {
            if ($inv) {
                $inv->recalcularMontoPagadoYEstado();
            }
        }

        return $this->respuestaExitosa(null, 'Registro de pago eliminado y saldos recalculados.');
    }
}

