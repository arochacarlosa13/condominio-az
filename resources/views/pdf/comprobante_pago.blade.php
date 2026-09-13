<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Pago RP - {{ $doc_numero }}</title>
    <style>
        @page {
            margin: 14px 18px 45px 18px;
        }
        footer {
            position: fixed;
            bottom: -35px;
            left: 0px;
            right: 0px;
            height: 28px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            padding: 3px 6px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #1e293b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .w-100 { width: 100%; }
        .collapse { border-collapse: collapse; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }

        .text-navy { color: #0f172a; }
        .text-slate { color: #334155; }
        .text-muted { color: #64748b; }
        .text-success { color: #15803d; }
        .font-mono { font-family: monospace; }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 5px;
        }
        .condo-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .rif-badge {
            display: inline-block;
            background-color: #e2e8f0;
            color: #334155;
            padding: 1px 5px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 2px;
        }
        .condo-meta {
            font-size: 7.5px;
            color: #475569;
            margin-top: 3px;
            line-height: 1.25;
        }

        .rp-badge-box {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 5px 9px;
            text-align: right;
            border-radius: 3px;
        }
        .rp-type-title {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #0f172a;
        }
        .rp-doc-num {
            font-size: 11px;
            font-weight: 800;
            color: #1e293b;
            margin-top: 2px;
            font-family: monospace;
        }

        /* Info Grid */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .grid-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 6px 8px;
            vertical-align: top;
        }
        .card-header {
            font-size: 7.5px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        /* Transacción Card */
        .tx-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 7px 9px;
            margin-bottom: 8px;
        }

        /* Data Table */
        .table-title {
            font-size: 8px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #1e293b;
        }
        .data-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-row {
            background-color: #f1f5f9 !important;
            font-weight: bold;
        }

        /* Seal / QR Box */
        .seal-box {
            border: 1px dashed #94a3b8;
            background-color: #f8fafc;
            padding: 6px 8px;
            border-radius: 4px;
            margin-top: 8px;
        }

        /* Draft Watermark */
        .watermark-draft {
            position: fixed;
            top: 32%;
            left: 5%;
            width: 90%;
            text-align: center;
            font-size: 32px;
            font-weight: 900;
            color: rgba(225, 29, 72, 0.13);
            text-transform: uppercase;
            letter-spacing: 3px;
            transform: rotate(-28deg);
            z-index: -1000;
            border: 3px dashed rgba(225, 29, 72, 0.18);
            padding: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    @if($es_borrador)
        <div class="watermark-draft">
            BORRADOR • PREVISUALIZACIÓN<br>
            <span style="font-size: 13px; font-weight: normal; letter-spacing: 1.5px; color: rgba(225, 29, 72, 0.18);">
                RECIBO RP EN ESTADO PENDIENTE • SIN VALIDEZ HASTA SU APROBACIÓN
            </span>
        </div>
    @endif

    <!-- 1. Encabezado Sobrio del Comprobante RP -->
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="condo-title">{{ strtoupper($condominio->nombre ?? 'CONDOMINIO RESIDENCIAL') }}</div>
                <span class="rif-badge">RIF: {{ $condominio->rif ?? 'J-300576531' }}</span>
                <div class="condo-meta">
                    {{ $condominio->direccion ?? 'Av. Principal Condominio' }}<br>
                    Contacto: {{ $condominio->telefono ?? '0414-0000000' }} | {{ $condominio->email ?? 'administracion@condominio.com' }}
                </div>
            </td>
            <td style="width: 42%; vertical-align: top;">
                <div class="rp-badge-box" style="{{ $es_borrador ? 'border-color: #f59e0b; background-color: #fffbeb;' : '' }}">
                    <div class="rp-type-title" style="{{ $es_borrador ? 'color: #b45309;' : '' }}">
                        {{ $es_borrador ? 'PREVISUALIZACIÓN DE RECIBO (BORRADOR RP)' : 'COMPROBANTE OFICIAL DE PAGO (RP)' }}
                    </div>
                    <div class="rp-doc-num" style="{{ $es_borrador ? 'color: #b45309;' : '' }}">
                        N° {{ $doc_numero }}
                    </div>
                    <div style="font-size: 7px; color: #64748b; margin-top: 2px;">
                        @if($es_borrador)
                            <strong style="color: #d97706;">ESTADO: PENDIENTE DE CONCILIACIÓN</strong>
                        @else
                            Fecha de Conciliación: <strong>{{ \Carbon\Carbon::parse($fecha_emision)->format('d/m/Y') }}</strong>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. Ficha de Inmueble y Residente -->
    <table class="grid-table">
        <tr>
            <td class="grid-card" style="width: 49%;">
                <div class="card-header">DATOS DEL INMUEBLE / UNIDAD</div>
                <table class="w-100 collapse">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Unidad / Apto:</td>
                        <td class="font-bold text-navy">Apartamento {{ $apartamento->numero ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Piso / Ubicación:</td>
                        <td class="font-bold text-navy">Piso {{ $apartamento->piso ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alícuota Aplicada:</td>
                        <td class="font-bold text-navy">{{ number_format($alicuota_percent, 4, ',', '.') }}%</td>
                    </tr>
                </table>
            </td>
            <td style="width: 2%;"></td>
            <td class="grid-card" style="width: 49%;">
                <div class="card-header">DATOS DEL PROPIETARIO / RESIDENTE</div>
                <table class="w-100 collapse">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Nombre Completo:</td>
                        <td class="font-bold text-navy">{{ $propietario->nombre_completo ?? 'Residente Registrado' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cédula / RIF:</td>
                        <td class="font-bold text-navy">{{ $propietario->cedula_rif ?? 'V-00000000' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teléfono Contacto:</td>
                        <td class="font-bold text-navy">{{ $propietario->telefono ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 3. Ficha de la Transacción Bancaria Realizada -->
    <div class="tx-card">
        <div style="font-size: 7.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px;">
            DETALLES DE LA TRANSACCIÓN Y CONCILIACIÓN BANCARIA
        </div>
        <table class="w-100 collapse" style="font-size: 7.5px;">
            <tr>
                <td style="width: 25%; vertical-align: top;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">Forma de Pago:</span>
                    <strong class="text-uppercase text-navy">{{ str_replace('_', ' ', $payment->metodo_pago ?? 'N/A') }}</strong>
                </td>
                <td style="width: 25%; vertical-align: top;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">Banco Emisor:</span>
                    <strong class="text-navy">{{ $payment->banco ?? 'N/A' }}</strong>
                </td>
                <td style="width: 25%; vertical-align: top;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">N° Referencia Bancaria:</span>
                    <strong class="text-navy font-mono" style="font-size: 8.5px;">{{ $payment->referencia ?? 'S/R' }}</strong>
                </td>
                <td style="width: 25%; vertical-align: top; text-align: right;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">Fecha de Pago:</span>
                    <strong class="text-navy">{{ !empty($payment->fecha_pago) ? \Carbon\Carbon::parse($payment->fecha_pago)->format('d/m/Y') : date('d/m/Y') }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 5px; vertical-align: top;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">Cuenta/Pago Móvil Destino:</span>
                    <span class="text-slate">
                        @if($payment->cuentaBancaria)
                            {{ $payment->cuentaBancaria->banco_nombre }} - {{ $payment->cuentaBancaria->es_pago_movil ? 'Pago Móvil: ' . $payment->cuentaBancaria->telefono_pago_movil : 'Cta: ' . $payment->cuentaBancaria->numero_cuenta }}
                        @else
                            {{ $condominio->banco_nombre ?? 'BNC' }} - Cta: {{ $condominio->cuenta_bancaria_bs ?? '0191 0514 8221 0001 8351' }}
                        @endif
                    </span>
                </td>
                <td colspan="2" style="padding-top: 5px; vertical-align: top; text-align: right;">
                    <span class="text-muted" style="font-size: 6.8px; display: block; font-weight: bold; text-transform: uppercase;">Monto Total Abonado:</span>
                    <strong style="font-size: 11px; color: #0f172a; font-family: monospace;">
                        Bs. {{ number_format((float)$payment->monto, 2, ',', '.') }}
                    </strong>
                    <span style="font-size: 7px; color: #475569; display: block;">
                        (Equivalente: <strong>${{ number_format(((float)$payment->monto / (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50))), 2, ',', '.') }} USD</strong> | Tasa BCV: Bs. {{ number_format((float)($payment->tasa_cambio ?: 36.50), 2, ',', '.') }})
                    </span>
                </td>
            </tr>
        </table>

        @php
            $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();
            $tasaPago = (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50));
            $montoTotalPago = (float)$payment->monto;

            $invoicesProcessed = [];
            $montoRestanteParaDistribuir = $montoTotalPago;
            $sumTotalAplicadoBs = 0;

            foreach ($coveredInvoices as $invCov) {
                $totalRiBs = (float)($invCov->monto_total_bs_efectivo > 0 ? $invCov->monto_total_bs_efectivo : $invCov->monto_total);
                $totalRiUsd = (float)($invCov->monto_total_usd > 0 ? $invCov->monto_total_usd : ($totalRiBs / $tasaPago));

                // Calcular saldo pendiente real antes de este pago
                $pagadoHistorico = (float)$invCov->monto_pagado;
                if ($payment->estado === 'aprobado') {
                    // Si ya está aprobado, $invCov->monto_pagado ya incluye este pago
                    $saldoPendienteAntes = $totalRiBs;
                } else {
                    $saldoPendienteAntes = max(0, $totalRiBs - $pagadoHistorico);
                }
                $deudaRequerida = $saldoPendienteAntes > 0 ? $saldoPendienteAntes : $totalRiBs;

                // El monto aplicado a esta factura NO puede superar su deuda total
                if (isset($invCov->pivot->monto_aplicado) && (float)$invCov->pivot->monto_aplicado > 0 && (float)$invCov->pivot->monto_aplicado <= $deudaRequerida) {
                    $montoAplicadoBs = (float)$invCov->pivot->monto_aplicado;
                } else {
                    $montoAplicadoBs = min($montoRestanteParaDistribuir, $deudaRequerida);
                }

                $sumTotalAplicadoBs += $montoAplicadoBs;
                $montoRestanteParaDistribuir = max(0, $montoRestanteParaDistribuir - $montoAplicadoBs);

                $esPagadoTotal = ($montoAplicadoBs >= ($deudaRequerida - 0.05)) || ($invCov->estado === 'pagado');

                $invoicesProcessed[] = [
                    'inv' => $invCov,
                    'numero_factura' => $invCov->numero_factura ?? 'RI2026-X',
                    'periodo' => $invCov->periodo ?? '-',
                    'total_usd' => $totalRiUsd,
                    'total_bs' => $totalRiBs,
                    'monto_aplicado_bs' => $montoAplicadoBs,
                    'pagado_total' => $esPagadoTotal,
                ];
            }

            $excedenteBs = max(0, $montoTotalPago - $sumTotalAplicadoBs);
            $excedenteUsd = round($excedenteBs / $tasaPago, 2);
        @endphp

        @if($payment->notaCreditoGenerada || $excedenteBs > 0.01)
            <div style="margin-top: 5px; padding: 4px 6px; background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 3px; font-size: 7px; color: #166534;">
                <strong>✨ EXCEDENTE A FAVOR REGISTRADO:</strong> Este pago cubrió la totalidad adeudada (Bs. {{ number_format($sumTotalAplicadoBs, 2, ',', '.') }}) y generó un excedente a favor de <strong>Bs. {{ number_format($payment->notaCreditoGenerada?->monto_original ?? $excedenteBs, 2, ',', '.') }} (${{ number_format($payment->notaCreditoGenerada?->monto_original_usd ?? $excedenteUsd, 2, ',', '.') }} USD)</strong> acreditado en la <strong>Nota de Crédito {{ $payment->notaCreditoGenerada?->numero_nota_credito ?? 'automática' }}</strong> para ser descontado en los próximos recibos de cobro.
            </div>
        @elseif($payment->metodo_pago === 'nota_credito' || $payment->credit_note_id)
            <div style="margin-top: 5px; padding: 4px 6px; background-color: #f0fdfa; border: 1px solid #99f6e4; border-radius: 3px; font-size: 7px; color: #115e59;">
                <strong>🎟️ PAGO POR NOTA DE CRÉDITO:</strong> Pago financiado automáticamente utilizando saldo a favor disponible de la <strong>Nota de Crédito {{ $payment->creditNote?->numero_nota_credito ?? $payment->referencia }}</strong>.
            </div>
        @endif
    </div>

    <!-- 4. Tabla de Correlación Financiera (Recibos RI vs Montos Aplicados en este RP) -->
    <div class="table-title">RELACIÓN DE RECIBOS DE COBRO (RI) SALDADOS Y APLICACIÓN DE FONDOS</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%; text-align: left;">N° Recibo RI / Concepto</th>
                <th style="width: 20%; text-align: right;">Monto Total RI ($ USD)</th>
                <th style="width: 20%; text-align: right;">Monto Total RI (Bs. Equiv)</th>
                <th style="width: 20%; text-align: right;">Monto Aplicado (Bs.)</th>
                <th style="width: 15%; text-align: center;">Estado Recibo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoicesProcessed as $item)
                <tr>
                    <td class="font-bold text-navy">
                        Recibo {{ $item['numero_factura'] }}<br>
                        <span style="color: #64748b; font-weight: normal; font-size: 7px;">Período: {{ $item['periodo'] }}</span>
                    </td>
                    <td class="text-right text-slate font-bold">
                        ${{ number_format($item['total_usd'], 2, ',', '.') }} USD
                    </td>
                    <td class="text-right text-slate font-bold font-mono">
                        Bs. {{ number_format($item['total_bs'], 2, ',', '.') }}
                    </td>
                    <td class="text-right font-bold text-navy font-mono" style="font-size: 8.5px;">
                        Bs. {{ number_format($item['monto_aplicado_bs'], 2, ',', '.') }}
                    </td>
                    <td class="text-center font-bold">
                        @if($item['pagado_total'])
                            <span style="color: #15803d; background-color: #dcfce7; padding: 1px 4px; border-radius: 2px; font-size: 6.5px;">PAGADO TOTAL</span>
                        @else
                            <span style="color: #b45309; background-color: #fef3c7; padding: 1px 4px; border-radius: 2px; font-size: 6.5px;">ABONO PARCIAL</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted" style="padding: 8px;">
                        Recibo de cobro individual general.
                    </td>
                </tr>
            @endforelse

            @if($payment->notaCreditoGenerada || $excedenteBs > 0.01)
                <tr style="background-color: #f0fdf4; border-top: 1.5px solid #22c55e;">
                    <td class="font-bold text-navy" style="color: #15803d;">
                        <strong>EXCEDENTE A FAVOR / NOTA DE CRÉDITO</strong><br>
                        <span style="color: #166534; font-size: 7px;">
                            @if($payment->notaCreditoGenerada)
                                Acreditado en Nota de Crédito N° <strong>{{ $payment->notaCreditoGenerada->numero_nota_credito }}</strong>
                            @elseif($payment->estado === 'aprobado')
                                Saldo a favor disponible para futuros recibos
                            @else
                                Excedente por conciliar (se emitirá Nota de Crédito al certificar)
                            @endif
                        </span>
                    </td>
                    <td class="text-right font-bold text-success font-mono">
                        +${{ number_format($payment->notaCreditoGenerada?->monto_original_usd ?? $excedenteUsd, 2, ',', '.') }} USD
                    </td>
                    <td class="text-right font-bold text-success font-mono">
                        +Bs. {{ number_format($payment->notaCreditoGenerada?->monto_original ?? $excedenteBs, 2, ',', '.') }}
                    </td>
                    <td class="text-right font-bold text-success font-mono" style="font-size: 8.5px; color: #15803d;">
                        +Bs. {{ number_format($payment->notaCreditoGenerada?->monto_original ?? $excedenteBs, 2, ',', '.') }}
                    </td>
                    <td class="text-center font-bold">
                        <span style="color: #15803d; background-color: #dcfce7; padding: 1px 4px; border-radius: 2px; font-size: 6.5px;">SALDO A FAVOR</span>
                    </td>
                </tr>
            @endif

            @if($payment->notaCreditoGenerada || $excedenteBs > 0.01)
                <tr class="totals-row" style="background-color: #f8fafc;">
                    <td colspan="3" class="text-right font-bold text-slate" style="font-size: 7.5px;">
                        Subtotal Aplicado a Recibos de Cobro (RI):
                    </td>
                    <td class="text-right font-bold text-navy font-mono" style="font-size: 8px;">
                        Bs. {{ number_format($sumTotalAplicadoBs, 2, ',', '.') }}
                    </td>
                    <td class="text-center font-bold text-slate" style="font-size: 6.5px;">CUOTA CUBIERTA</td>
                </tr>
                <tr class="totals-row" style="background-color: #ecfdf5;">
                    <td colspan="3" class="text-right font-bold text-success" style="font-size: 7.5px;">
                        Excedente Acreditado en Nota de Crédito:
                    </td>
                    <td class="text-right font-bold text-success font-mono" style="font-size: 8px;">
                        Bs. {{ number_format($payment->notaCreditoGenerada?->monto_original ?? $excedenteBs, 2, ',', '.') }}
                    </td>
                    <td class="text-center font-bold text-success" style="font-size: 6.5px;">A FAVOR</td>
                </tr>
            @endif

            <tr class="totals-row" style="background-color: #e2e8f0;">
                <td colspan="3" class="text-right font-bold text-navy" style="font-size: 8px;">
                    TOTAL CONCILIADO EN ESTE COMPROBANTE RP:
                </td>
                <td class="text-right font-bold text-navy font-mono" style="font-size: 9.5px;">
                    Bs. {{ number_format((float)$payment->monto, 2, ',', '.') }}
                </td>
                <td class="text-center font-bold text-success" style="font-size: 7px;">
                    CONCILIADO
                </td>
            </tr>
        </tbody>
    </table>

    @if(!empty($payment->observaciones))
        <div style="font-size: 7px; color: #334155; margin-bottom: 6px; background-color: #f8fafc; padding: 4px 6px; border-left: 3px solid #94a3b8;">
            <strong>Observaciones de Conciliación:</strong> {{ $payment->observaciones }}
        </div>
    @endif

    <!-- 5. Código QR y Certificación Digital de Autenticidad en Vivo -->
    <div class="seal-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 75px; text-align: center; vertical-align: middle;">
                    @if(!empty($qrCodeBase64))
                        <img src="{{ $qrCodeBase64 }}" width="65" height="65" alt="Código QR" style="border: 1px solid #cbd5e1; padding: 2px; background: #fff; border-radius: 3px;" />
                    @else
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($urlCertificacion ?? url('/certificacion/pago/'.$payment->id)) }}" width="65" height="65" alt="Código QR" style="border: 1px solid #cbd5e1; padding: 2px; background: #fff; border-radius: 3px;" />
                    @endif
                </td>
                <td style="vertical-align: middle; padding-left: 8px; text-align: left;">
                    <div style="font-size: 8.5px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                        ESCANEE ESTE CÓDIGO QR PARA CERTIFICAR AUTENTICIDAD EN VIVO
                    </div>
                    <div style="font-size: 7px; color: #475569; margin-top: 2px; line-height: 1.25;">
                        Escanee el código con la cámara de su teléfono celular para verificar la validez, conciliación e inscripción oficial de este comprobante en la contabilidad del condominio.
                    </div>
                    <div style="font-size: 6.5px; color: #64748b; margin-top: 3px; font-family: monospace;">
                        Enlace directo: <strong>{{ $urlCertificacion ?? url('/certificacion/pago/'.$payment->id) }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 6. Footer de Marketing e Identidad Fijo al Final de la Hoja -->
    <footer>
        <div style="font-size: 7.5px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase;">
            DOCUMENTO EMITIDO MEDIANTE EL SISTEMA DE GESTIÓN INTELIGENTE DE CONDOMINIOS • AZPRO
        </div>
        <div style="font-size: 6.8px; color: #475569; margin-top: 1px; font-style: italic;">
            "Transparencia total, innovación contable y control financiero en tiempo real para comunidades modernas."
        </div>
    </footer>

</body>
</html>
