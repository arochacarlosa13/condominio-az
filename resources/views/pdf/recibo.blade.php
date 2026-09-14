<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de Condominio - {{ $periodo_nombre ?? 'Aviso de Cobro' }}</title>
    <style>
        @page {
            margin: 12px 18px 45px 18px;
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
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        /* Utilidades Generales */
        .w-100 { width: 100%; }
        .collapse { border-collapse: collapse; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }

        /* Colores del Sistema */
        .text-navy { color: #1e293b; }
        .text-muted { color: #64748b; }
        .text-primary { color: #334155; }
        .text-success { color: #15803d; }
        .bg-navy { background-color: #f1f5f9; color: #0f172a; }
        .bg-slate { background-color: #f1f5f9; }
        .bg-light { background-color: #f8fafc; }
        .bg-emerald { background-color: #f0fdf4; }
        .border-slate { border: 1px solid #cbd5e1; }
        .border-bottom { border-bottom: 1px solid #cbd5e1; }

        /* Encabezado Principal */
        .header-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
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
            padding: 1px 4px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 2px;
        }
        .condo-meta {
            font-size: 7.5px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.25;
        }

        .doc-badge-box {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            text-align: right;
            border-radius: 3px;
        }
        .doc-type-title {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #0f172a;
        }
        .doc-period-title {
            font-size: 10px;
            font-weight: 800;
            color: #334155;
            margin-top: 1px;
        }
        .doc-meta-sub {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Ficha del Propietario e Inmueble (Cards Grid) */
        .info-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
        }
        .info-cell {
            padding: 4px 6px;
            vertical-align: top;
            border-right: 1px solid #e2e8f0;
        }
        .info-label {
            font-size: 6.8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 1px;
        }
        .info-value {
            font-size: 8.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .pill-alicuota {
            display: inline-block;
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 1px 5px;
            font-weight: 800;
            font-size: 8.5px;
            border-radius: 2px;
        }

        /* Tabla de Conceptos y Gastos */
        .table-section-title {
            font-size: 8.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .expenses-table th {
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 2.5px 4px;
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
        }
        .expenses-table td {
            padding: 2px 4px;
            font-size: 7.8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }
        .row-alt { background-color: #f8fafc; }
        .badge-ali {
            background-color: #f1f5f9;
            color: #334155;
            padding: 0.5px 3px;
            font-size: 6.8px;
            font-weight: bold;
            border-radius: 2px;
            border: 1px solid #cbd5e1;
        }
        .totals-bar {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            border-top: 1.5px solid #cbd5e1;
            border-bottom: 1.5px solid #cbd5e1;
        }

        /* Fondo de Reserva & Resumen Financiero Side-by-Side */
        .summary-wrapper-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        .summary-box-left {
            width: 52%;
            vertical-align: top;
            padding-right: 4px;
        }
        .summary-box-right {
            width: 48%;
            vertical-align: top;
            padding-left: 4px;
        }

        .mini-card {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            border-radius: 3px;
            padding: 4px 6px;
            margin-bottom: 4px;
        }
        .mini-card-header {
            font-size: 7.2px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }

        .calc-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .calc-row td {
            padding: 1.5px 0;
            font-size: 7.8px;
        }

        .hero-total-usd {
            background-color: #f0fdf4;
            border: 1.5px solid #16a34a;
            border-radius: 3px;
            padding: 3px 6px;
            margin-bottom: 3px;
        }
        .hero-total-bs {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            border-radius: 3px;
            padding: 4px 6px;
        }

        /* Instrucciones de Pago y Notas Legales */
        .legal-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 3px 5px;
            font-size: 6.8px;
            color: #334155;
            line-height: 1.25;
            text-align: justify;
        }

        /* Marca de Agua de Borrador */
        .watermark-container {
            position: fixed;
            top: 26%;
            left: 0;
            right: 0;
            text-align: center;
            z-index: -1000;
            transform: rotate(-30deg);
        }
        .watermark-text {
            font-size: 62px;
            font-weight: 900;
            color: #dc2626;
            opacity: 0.14;
            letter-spacing: 8px;
            border: 5px dashed #dc2626;
            padding: 10px 20px;
            display: inline-block;
        }
        .watermark-sub {
            font-size: 20px;
            font-weight: 800;
            color: #dc2626;
            opacity: 0.18;
            letter-spacing: 4px;
            margin-top: 6px;
        }
        .draft-banner {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px dashed #d97706;
            text-align: center;
            font-weight: 800;
            font-size: 7.2px;
            padding: 2px 4px;
            margin-bottom: 4px;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    @php
        $es_extraordinario = (!empty($es_extraordinario) || ($invoice?->tipo_recibo === 'extraordinario'));
    @endphp

    <!-- Marca de Agua para Estado Borrador -->
    @if(!empty($es_borrador))
        <div class="watermark-container">
            <div class="watermark-text">BORRADOR</div>
            <div class="watermark-sub">DOCUMENTO NO VÁLIDO PARA COBRO OFICIAL</div>
        </div>
        <div class="draft-banner">
            VISTA PREVIA EN BORRADOR - DOCUMENTO PRELIMINAR SUJETO A CERTIFICACIÓN Y BLOQUEO POR ADMINISTRACIÓN
        </div>
    @endif

    <!-- Título Principal en Parte Superior Central para Recibo de Pago -->
    @if(!empty($payment))
        <div style="text-align: center; font-size: 13px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; color: #0f172a; padding: 4px 0; margin-bottom: 6px; border-bottom: 2px solid #0f172a; background-color: #f1f5f9;">
            {{ $es_extraordinario ? 'RECIBO DE PAGO - CUOTA EXTRAORDINARIA' : 'RECIBO DE PAGO' }}
        </div>
    @endif

    <!-- 1. Encabezado Institucional y Metadatos de Facturación -->
    <table class="header-card">
        <tr>
            <td style="width: 62%; vertical-align: top;">
                <div class="condo-title">{{ $condominio->nombre ?? 'CONDOMINIO RESIDENCIAS' }}</div>
                <div>
                    <span class="rif-badge">RIF: {{ $condominio->rif ?? 'J-00000000-0' }}</span>
                </div>
                <div class="condo-meta">
                    {{ $condominio->direccion ?? 'Caracas, Distrito Capital, Venezuela' }}
                    @if(!empty($condominio->telefono)) | Teléf: {{ $condominio->telefono }} @endif
                    @if(!empty($condominio->email)) | Email: {{ $condominio->email }} @endif
                </div>
                @if($es_extraordinario && !empty($invoice?->titulo_proyecto))
                    <div style="margin-top: 3px; font-size: 7.8px; font-weight: 800; color: #6d28d9; background-color: #f5f3ff; border: 1px solid #ddd6fe; padding: 1px 4px; border-radius: 2px; display: inline-block;">
                         PROYECTO EXTRAORDINARIO: {{ strtoupper($invoice->titulo_proyecto) }}
                    </div>
                @endif
            </td>
            <td style="width: 38%; vertical-align: middle;">
                <div class="doc-badge-box" style="{{ $es_extraordinario ? 'border-color: #c4b5fd; background-color: #fbfbfe;' : '' }}">
                    <div class="doc-type-title" style="{{ $es_extraordinario ? 'color: #5b21b6;' : '' }}">
                        @if(!empty($payment))
                            {{ $es_extraordinario ? 'COMPROBANTE DE PAGO EXTRAORDINARIO' : 'COMPROBANTE DE PAGO' }}
                        @else
                            {{ $es_extraordinario ? 'AVISO DE COBRO - CUOTA EXTRAORDINARIA' : 'ESTADO DE CUENTA Y EXPENSAS' }}
                        @endif
                    </div>
                    <div class="doc-period-title">{{ strtoupper($periodo_nombre ?? 'PERÍODO') }}</div>
                    <div class="doc-meta-sub">
                        N° DOC: <strong>{{ $doc_numero ?? ('FAC-' . str_pad($payment?->id ?? 1, 5, '0', STR_PAD_LEFT)) }}</strong>
                        <br>
                        Emisión: {{ $fecha_emision ?? date('Y-m-d') }} | Vence: 5 días
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. Ficha del Inmueble y Propietario -->
    <table class="info-grid-table">
        <tr>
            <td class="info-cell" style="width: 32%;">
                <div class="info-label">Inmueble / Unidad</div>
                <div class="info-value">Apartamento {{ $apartamento->numero ?? 'N/A' }}</div>
                <div style="font-size: 7px; color: #64748b; margin-top: 1px;">
                    Piso {{ $apartamento->piso ?? 'PB' }} - Torre/Grupo {{ $apartamento->grupo_alicuota ?? '1' }}
                </div>
            </td>
            <td class="info-cell" style="width: 44%;">
                <div class="info-label">Propietario / Residente</div>
                <div class="info-value text-uppercase" style="font-size: 8px;">
                    {{ $propietario->nombre_completo ?? 'PROPIETARIO REGISTRADO' }}
                </div>
                <div style="font-size: 7px; color: #64748b; margin-top: 1px;">
                    C.I. / RIF: <strong>{{ $propietario->cedula ?? 'V-00000000' }}</strong> | {{ $propietario->email ?? ($payment?->invoice?->apartamento?->propietarios?->first()?->email ?? 'contacto@condominio.com') }}
                </div>
            </td>
            @php
                $detallesApto = $apartamento?->alicuotas_detalle ?? [
                    ['numero' => 1, 'nombre' => 'Gastos Generales', 'porcentaje' => ($alicuota_percent ?? 3.03460000)]
                ];
            @endphp
            <td class="info-cell" style="width: 26%; border-right: none; text-align: right;">
                <div class="info-label" style="text-align: right;">{{ $es_extraordinario ? 'Modalidad de Cobro' : 'Alícuotas Inmueble' }}</div>
                @if($es_extraordinario)
                    <div class="pill-alicuota" style="background-color: #ede9fe; color: #5b21b6;">
                         Partes Iguales
                    </div>
                @else
                    @foreach($detallesApto as $aliItem)
                        @if($aliItem['numero'] === 1)
                            <div class="pill-alicuota">
                                {{ $aliItem['nombre'] }}: {{ number_format($aliItem['porcentaje'], 4, ',', '.') }}%
                            </div>
                        @else
                            <div style="font-size: 6.8px; color: #475569; margin-top: 1px;">
                                {{ $aliItem['nombre'] }}: <strong>{{ number_format($aliItem['porcentaje'], 4, ',', '.') }}%</strong>
                            </div>
                        @endif
                    @endforeach
                @endif
            </td>
        </tr>
    </table>

    <!-- 2.B Ficha Detalle del Pago Registrado y Conciliado por Administración -->
    @if(!empty($payment))
        <div style="margin-top: 5px; margin-bottom: 6px; background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 4px; padding: 6px 8px;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; border-bottom: 1px solid #bbf7d0; padding-bottom: 3px;">
                <tr>
                    <td style="font-size: 8.5px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">
                        💳 DETALLES DE LA TRANSACCIÓN Y COMPROBANTE DE PAGO BANCARIO
                    </td>
                    <td style="text-align: right;">
                        <span style="background-color: #166534; color: #ffffff; padding: 1px 6px; border-radius: 2px; font-size: 7.5px; font-weight: bold;">
                            RECIBO N° {{ $payment->numero_recibo_pago ?? $doc_numero }}
                        </span>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; font-size: 7.5px; color: #1e293b;">
                <tr>
                    <td style="width: 25%; vertical-align: top; padding-right: 4px;">
                        <span style="color: #64748b; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">Forma / Método de Pago:</span>
                        <strong style="color: #0f172a; text-transform: uppercase;">{{ str_replace('_', ' ', $payment->metodo_pago ?? 'N/A') }}</strong>
                    </td>
                    <td style="width: 25%; vertical-align: top; padding-right: 4px;">
                        <span style="color: #64748b; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">Banco Emisor / Destino:</span>
                        <strong style="color: #0f172a;">{{ $payment->banco ?? 'N/A' }}</strong>
                    </td>
                    <td style="width: 25%; vertical-align: top; padding-right: 4px;">
                        <span style="color: #64748b; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">N° Referencia Bancaria:</span>
                        <strong style="color: #0f172a; font-family: monospace; font-size: 8.5px;">{{ $payment->referencia ?? 'S/R' }}</strong>
                    </td>
                    <td style="width: 25%; vertical-align: top; text-align: right;">
                        <span style="color: #64748b; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">Fecha del Depósito/Pago:</span>
                        <strong style="color: #0f172a;">{{ !empty($payment->fecha_pago) ? \Carbon\Carbon::parse($payment->fecha_pago)->format('d/m/Y') : date('d/m/Y') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 5px; vertical-align: top;">
                        <span style="color: #64748b; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">Notas / Observaciones del Pago:</span>
                        <span style="color: #334155; font-style: italic;">{{ $payment->observaciones ?: 'Pago notificado y verificado conformemente por administración en la cuenta bancaria del condominio.' }}</span>
                    </td>
                    <td colspan="2" style="padding-top: 5px; vertical-align: top; text-align: right;">
                        <span style="color: #166534; font-size: 6.8px; display: block; text-transform: uppercase; font-weight: bold;">Monto Notificado y Aplicado:</span>
                        <strong style="font-size: 11px; color: #15803d; font-family: monospace;">
                            Bs. {{ number_format((float)$payment->monto, 2, ',', '.') }}
                        </strong>
                        <span style="font-size: 7.2px; color: #166534; display: block;">
                            (Equivalente: <strong>${{ number_format(((float)$payment->monto / (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50))), 2, ',', '.') }} USD</strong> | Tasa BCV: Bs. {{ number_format((float)($payment->tasa_cambio ?: 36.50), 2, ',', '.') }})
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <!-- 3. Estructura Detallada de Gastos Comunes -->
    <div class="table-section-title">Estructura de Gastos y Conceptos del Período</div>
    <table class="expenses-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 6%;">Grupo</th>
                <th style="width: 64%; text-align: left;">Descripción del Concepto / Gasto</th>
                <th style="width: 13%; text-align: right;">Total Edificio ($)</th>
                <th style="width: 13%; text-align: right;">Cuota Inmueble ($)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $alicuotaFactor = ($alicuota_percent ?? ($apartamento->alicuota > 0 ? $apartamento->alicuota : 3.03460000)) / 100;
                $sumTotalEdificio = 0;
                $sumTotalAlicuota = 0;
                $sumGastosNoComunes = 0;

                $gastosComunes = array_filter($gastos ?? [], fn($g) => empty($g['es_no_comun']));
                $gastosNoComunes = array_filter($gastos ?? [], fn($g) => !empty($g['es_no_comun']));
            @endphp

            @forelse($gastosComunes as $idx => $gasto)
                @php
                    $montoItem = (float)($gasto['monto'] ?? 0);
                    $aliGroup = strval($gasto['ali'] ?? '1');
                    
                    if (isset($gasto['alicu']) && empty($es_borrador)) {
                        $montoAlicu = (float)$gasto['alicu'];
                    } else {
                        $aliIdOrGroup = $gasto['condominio_alicuota_id'] ?? ($gasto['ali'] ?? '1');
                        $percentRow = $apartamento ? $apartamento->getAlicuota($aliIdOrGroup) : ($aliGroup === '1' ? ($alicuota_percent ?? 3.03460000) : 0.0);
                        if ($percentRow <= 0 && $aliGroup === '1') {
                            $percentRow = $alicuota_percent ?? 3.03460000;
                        }
                        $factorRow = $percentRow / 100;
                        $montoAlicu = round($montoItem * $factorRow, 2);
                    }

                    $sumTotalEdificio += $montoItem;
                    $sumTotalAlicuota += $montoAlicu;
                @endphp
                <tr class="{{ $idx % 2 == 1 ? 'row-alt' : '' }}">
                    <td class="text-center font-bold" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td class="text-center"><span class="badge-ali">Ali {{ $aliGroup }}</span></td>
                    <td class="font-bold text-navy">{{ $gasto['concepto'] }}</td>
                    <td class="text-right">{{ number_format($montoItem, 2, ',', '.') }}</td>
                    <td class="text-right font-bold text-primary">{{ number_format($montoAlicu, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 10px; color: #94a3b8;">
                        No se registraron gastos directos en este período.
                    </td>
                </tr>
            @endforelse

            <!-- Subtotal Gastos Comunes -->
            <tr class="totals-bar">
                <td colspan="3" class="text-right font-bold" style="padding: 2.5px 4px;">
                    SUBTOTAL GASTOS COMUNES DEL MES:
                </td>
                <td class="text-right font-bold" style="padding: 2.5px 4px;">
                    ${{ number_format($sumTotalEdificio, 2, ',', '.') }}
                </td>
                <td class="text-right font-bold text-primary" style="padding: 2.5px 4px;">
                    ${{ number_format($sumTotalAlicuota, 2, ',', '.') }}
                </td>
            </tr>

            <!-- Sección de Gastos No Comunes / Cargos Particulares Directos al Inmueble -->
            @if(!empty($gastosNoComunes))
                <tr style="background-color: #e2e8f0; color: #0f172a; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                    <td colspan="5" class="text-left font-bold" style="padding: 3px 6px; font-size: 7.5px; letter-spacing: 0.5px;">
                        GASTOS NO COMUNES / CARGOS PARTICULARES DIRECTOS AL INMUEBLE:
                    </td>
                </tr>
                @foreach($gastosNoComunes as $idxNc => $gNc)
                    @php
                        $montoNc = (float)($gNc['alicu'] ?? ($gNc['monto'] ?? 0));
                        $sumGastosNoComunes += $montoNc;
                    @endphp
                    <tr style="background-color: #f0fdf4;">
                        <td class="text-center font-bold" style="color: #15803d;">NC</td>
                        <td class="text-center"><span class="badge-ali" style="background-color: #dcfce7; color: #166534; border-color: #86efac;">Directo</span></td>
                        <td class="font-bold text-navy">{{ $gNc['concepto'] }}</td>
                        <td class="text-right text-muted">${{ number_format($montoNc, 2, ',', '.') }}</td>
                        <td class="text-right font-bold text-success">${{ number_format($montoNc, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #dcfce7; font-weight: bold; border-top: 1px solid #16a34a;">
                    <td colspan="4" class="text-right font-bold" style="padding: 2.5px 4px; color: #14532d;">
                        TOTAL CARGOS PARTICULARES DIRECTOS:
                    </td>
                    <td class="text-right font-bold text-success" style="padding: 2.5px 4px;">
                        ${{ number_format($sumGastosNoComunes, 2, ',', '.') }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- 4. Resumen Financiero y Conversión Oficial BCV (Distribución en 2 Columnas) -->
    @php
        $fondoAcumulado = $condominio->fondo_reserva_acumulado ?? 2997.06;
        if ($es_extraordinario) {
            $fondoMes = 0;
            $fondoAlicu = 0;
            $totalMesEdificio = $sumTotalEdificio;
            $totalMesAlicuota = $sumTotalAlicuota + $sumGastosNoComunes;
        } else {
            $fondoMes = $fondo_mes_monto ?? ($sumTotalEdificio * 0.10);
            $fondoAlicu = $fondo_mes_alicuota ?? ($fondoMes * $alicuotaFactor);
            $totalMesEdificio = $sumTotalEdificio + $fondoMes;
            $totalMesAlicuota = $sumTotalAlicuota + $fondoAlicu + $sumGastosNoComunes;
        }

        $tasaBcv = (float)($invoice?->tasa_efectiva ?? ($payment?->tasa_cambio ?? ($condominio->tasa_cambio ?? 36.50)));
        $deudaMesUsd = (float)($invoice?->monto_total_usd > 0 ? $invoice->monto_total_usd : $totalMesAlicuota);
        $totalBolivares = (float)($invoice?->monto_total_bs_efectivo > 0 ? $invoice->monto_total_bs_efectivo : round($deudaMesUsd * $tasaBcv, 2));
        $tasaCongelada = $invoice?->tasa_congelada ?? false;
        $diasTranscurridos = $invoice?->dias_transcurridos ?? 0;
    @endphp

    <table class="summary-wrapper-table">
        <tr>
            <!-- Columna Izquierda: Fondo de Reserva y Canales Bancarios -->
            <td class="summary-box-left">
                <!-- Mini Card: Fondos y Provisiones de Reserva (Solo en recibo ordinario del mes) -->
                @if(!$es_extraordinario)
                    <div class="mini-card">
                        <div class="mini-card-header">Fondos y Provisiones de Reserva</div>
                        <table class="calc-row">
                            <tr>
                                <td class="font-bold text-navy" style="width: 55%;">FONDO DE RESERVA MENSUAL:</td>
                                <td class="text-right" style="width: 25%;">${{ number_format($fondoMes, 2, ',', '.') }}</td>
                                <td class="text-right font-bold text-primary" style="width: 20%;">${{ number_format($fondoAlicu, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 7px;" colspan="3">
                                    Saldo Acumulado en Custodia del Edificio: <strong>${{ number_format($fondoAcumulado, 2, ',', '.') }} USD</strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                <!-- Mini Card: Cuentas Bancarias e Instrucciones -->
                <div class="mini-card" style="margin-bottom: 0;">
                    <div class="mini-card-header">Canales de Pago Autorizados del Edificio</div>
                    <div style="font-size: 7px; line-height: 1.35; color: #334155;">
                        @if(!empty($cuentasBancarias) && count($cuentasBancarias) > 0)
                            @foreach($cuentasBancarias as $cta)
                                <div>
                                    • <strong>{{ $cta->banco_nombre }}</strong> 
                                    @if($cta->es_pago_movil) (Pago Móvil: {{ $cta->telefono_pago_movil }} | {{ $cta->titular_identificacion }})
                                    @elseif($cta->numero_cuenta) ({{ strtoupper($cta->moneda) }}: Cta {{ $cta->numero_cuenta }})
                                    @endif
                                </div>
                            @endforeach
                        @else
                            • <strong>Transferencia / Pago Móvil en Bs.:</strong> {{ $condominio->banco_nombre ?? 'BNC' }} Cta: {{ $condominio->cuenta_bancaria_bs ?? '0191 0514 8221 0001 8351' }}<br>
                            • <strong>Depósito Divisas USD:</strong> {{ $condominio->banco_nombre ?? 'BNC' }} Cta: {{ $condominio->cuenta_bancaria_usd ?? '0191 0012 0223 1202 5152' }}<br>
                        @endif
                        • <strong>Titular:</strong> {{ strtoupper($condominio->nombre ?? 'CONDOMINIO RESIDENCIAL') }} | RIF: {{ $condominio->rif ?? 'J-300576531' }}
                    </div>
                </div>
            </td>

            <!-- Columna Derecha: Liquidación Final y Conversión BCV -->
            <td class="summary-box-right">
                <!-- Card de Liquidación Total -->
                <div class="mini-card" style="background-color: #f8fafc; border: 1.5px solid #cbd5e1;">
                    <div class="mini-card-header" style="color: #0f172a;">{{ $es_extraordinario ? 'Resumen de Liquidación Extraordinaria' : 'Resumen de Liquidación del Mes' }}</div>
                    
                    <table class="calc-row">
                        <tr>
                            <td class="text-muted">{{ $es_extraordinario ? 'Subtotal Conceptos Extraordinarios:' : 'Subtotal Cuota Gastos Comunes:' }}</td>
                            <td class="text-right font-bold">${{ number_format($sumTotalAlicuota, 2, ',', '.') }}</td>
                        </tr>
                        @if(!$es_extraordinario)
                            <tr>
                                <td class="text-muted">Aporte Fondo de Reserva (10%):</td>
                                <td class="text-right font-bold">+ ${{ number_format($fondoAlicu, 2, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted" style="font-size: 7px; color: #64748b;">Fondo de Reserva (0%):</td>
                                <td class="text-right font-bold" style="font-size: 7px; color: #64748b;">No Aplica ($0,00)</td>
                            </tr>
                        @endif
                    </table>

                    <!-- Caja Total Divisas USD -->
                    <div class="hero-total-usd" style="{{ $es_extraordinario ? 'border-color: #7c3aed; background-color: #f5f3ff;' : '' }}">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="font-size: 8.5px; font-weight: 800; color: {{ $es_extraordinario ? '#6d28d9' : '#15803d' }}; text-transform: uppercase;">
                                    {{ $es_extraordinario ? 'Total Cuota Extraordinaria:' : 'Total Cuota del Mes:' }}
                                </td>
                                <td style="font-size: 11px; font-weight: 900; color: {{ $es_extraordinario ? '#6d28d9' : '#15803d' }}; text-align: right;">
                                    ${{ number_format($deudaMesUsd, 2, ',', '.') }} USD
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Caja Total Bolívares Bs. -->
                    <div class="hero-total-bs">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="font-size: 7.5px; color: #475569;">
                                    Tasa Oficial BCV: <strong style="color: #0f172a;">Bs. {{ number_format($tasaBcv, 2, ',', '.') }}</strong>
                                    @if($tasaCongelada)
                                        <span style="color: #64748b; font-size: 6.5px; display: block;">(Tasa Fija día {{ $diasTranscurridos + 1 }} de {{ $condominio->dias_congelar_tasa ?? 5 }})</span>
                                    @elseif(!empty($condominio->mantener_tasa_emision_5_dias))
                                        <span style="color: #64748b; font-size: 6.5px; display: block;">(Tasa BCV del día)</span>
                                    @endif
                                </td>
                                <td style="font-size: 7px; color: #334155; text-align: right; text-transform: uppercase; font-weight: bold; vertical-align: top;">
                                    Total en Bolívares
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-size: 11.5px; font-weight: 900; color: #0f172a; text-align: right; padding-top: 1px;">
                                    Bs. {{ number_format($totalBolivares, 2, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 5. Cláusula Legal y Notificación de Pagos -->
    <div class="legal-box">
        <strong>AVISO LEGAL Y CONDICIONES DE PAGO:</strong> Vencimiento a los cinco (5) días continuos posteriores a su fecha de emisión. @if(!empty($condominio->mantener_tasa_emision_5_dias)) Durante los primeros cinco (5) días de emisión rige la tasa oficial de emisión fijada. A partir del sexto (6to) día continuo, el monto se recalcula automáticamente a la tasa oficial del día de pago suministrada por el Banco Central de Venezuela (BCV) de conformidad con los artículos 8 y 128 del Convenio Cambiario N° 1. @else Aplica tasa oficial suministrada por el Banco Central de Venezuela (BCV) a la fecha de pago de conformidad con lo previsto en los artículos 8 y 128 del Convenio Cambiario N° 1. @endif Por favor notifique su transferencia o pago a través del sistema web cargando su referencia o enviando soporte a <strong>{{ strtolower($condominio->email ?? 'administracion@condominio.com') }}</strong>. La morosidad mayor a tres (3) cuotas continuas dará inicio al cobro de recargos e intereses de conformidad con el documento de condominio.
    </div>

    <!-- Sello Criptográfico Digital y Validación Pública QR (Fase 3.1) -->
    @if(!empty($hashSha256))
        <div style="margin-top: 4px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 3px; padding: 3px 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    @if(!empty($qrCodeBase64))
                        <td style="width: 48px; vertical-align: middle; text-align: center; padding-right: 5px;">
                            <img src="{{ $qrCodeBase64 }}" style="width: 44px; height: 44px; border-radius: 2px; border: 1px solid #cbd5e1;" alt="QR Validación">
                        </td>
                    @endif
                    <td style="vertical-align: middle; font-size: 6.8px; color: #334155; line-height: 1.25;">
                        <div style="font-weight: 800; color: #0f172a; text-transform: uppercase; font-size: 7.2px; letter-spacing: 0.3px;">
                            🛡️ CERTIFICACIÓN DIGITAL Y SELLO CRIPTOGRÁFICO DE AUTENTICIDAD
                        </div>
                        <div>
                            Documento sellado digitalmente por <strong>AZPRO Condominio</strong>. Escanee el código QR para verificar la integridad e inalterabilidad de este aviso de cobro en tiempo real.
                        </div>
                        <div style="font-family: monospace; font-size: 6.5px; color: #0369a1; word-break: break-all; margin-top: 1px;">
                            <strong>HASH SHA-256:</strong> {{ $hashSha256 }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

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
