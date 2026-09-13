<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de Crédito NC - {{ $doc_numero }}</title>
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
            border-bottom: 2px solid #059669;
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

        .nc-badge-box {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1.5px solid #10b981;
            padding: 5px 9px;
            text-align: right;
            border-radius: 3px;
        }
        .nc-type-title {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #047857;
        }
        .nc-doc-num {
            font-size: 11.5px;
            font-weight: 900;
            color: #065f46;
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

        /* Highlight Balance Box */
        .balance-hero {
            background-color: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 4px;
            padding: 8px 10px;
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
            background-color: #065f46;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #065f46;
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
            background-color: #ecfdf5 !important;
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
    </style>
</head>
<body>

    <!-- 1. Encabezado Oficial -->
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
                <div class="nc-badge-box">
                    <div class="nc-type-title">
                        COMPROBANTE OFICIAL DE NOTA DE CRÉDITO (NC)
                    </div>
                    <div class="nc-doc-num">
                        N° {{ $doc_numero }}
                    </div>
                    <div style="font-size: 7px; color: #047857; margin-top: 2px;">
                        Fecha de Emisión: <strong>{{ \Carbon\Carbon::parse($fecha_emision)->format('d/m/Y') }}</strong> | Estado: <strong>{{ strtoupper($creditNote->estado) }}</strong>
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

    <!-- 3. Tarjeta Hero de Saldos Financieros -->
    <div class="balance-hero">
        <table class="w-100 collapse">
            <tr>
                <td style="width: 48%; vertical-align: middle; border-right: 1px dashed #86efac; padding-right: 8px;">
                    <span style="font-size: 7px; text-transform: uppercase; font-weight: bold; color: #047857; display: block;">
                        MONTO ORIGINAL ACREDITADO (EXCEDENTE)
                    </span>
                    <strong style="font-size: 13px; color: #065f46; font-family: monospace; display: block; margin-top: 1px;">
                        Bs. {{ number_format((float)$creditNote->monto_original, 2, ',', '.') }}
                    </strong>
                    <span style="font-size: 7.5px; color: #047857; font-weight: bold;">
                        ${{ number_format((float)$creditNote->monto_original_usd, 2, ',', '.') }} USD
                    </span>
                    <span style="font-size: 6.8px; color: #475569;">
                        (Tasa Emisión: Bs. {{ number_format((float)$creditNote->tasa_cambio, 2, ',', '.') }})
                    </span>
                </td>
                <td style="width: 52%; vertical-align: middle; padding-left: 10px; text-align: right;">
                    <span style="font-size: 7px; text-transform: uppercase; font-weight: bold; color: #047857; display: block;">
                        SALDO RESTANTE DISPONIBLE A LA FECHA
                    </span>
                    <strong style="font-size: 14px; color: #15803d; font-family: monospace; display: block; margin-top: 1px;">
                        Bs. {{ number_format((float)$creditNote->monto_disponible, 2, ',', '.') }}
                    </strong>
                    <span style="font-size: 8px; color: #15803d; font-weight: bold;">
                        ${{ number_format((float)$creditNote->monto_disponible_usd, 2, ',', '.') }} USD DISPONIBLE
                    </span>
                    <div style="margin-top: 2px;">
                        @if($creditNote->estado === 'disponible')
                            <span style="color: #15803d; background-color: #dcfce7; border: 1px solid #86efac; padding: 1px 5px; border-radius: 2px; font-size: 6.8px; font-weight: bold;">
                                ✓ DISPONIBLE AL 100%
                            </span>
                        @elseif($creditNote->estado === 'parcial')
                            <span style="color: #b45309; background-color: #fef3c7; border: 1px solid #fcd34d; padding: 1px 5px; border-radius: 2px; font-size: 6.8px; font-weight: bold;">
                                CONSUMIDA PARCIALMENTE
                            </span>
                        @else
                            <span style="color: #64748b; background-color: #e2e8f0; border: 1px solid #cbd5e1; padding: 1px 5px; border-radius: 2px; font-size: 6.8px; font-weight: bold;">
                                TOTALMENTE CONSUMIDA
                            </span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 4. Origen y Motivo del Crédito a Favor -->
    <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 3px; padding: 6px 8px; margin-bottom: 8px;">
        <div class="card-header">ORIGEN DEL CRÉDITO Y TRANSACCIÓN BANCARIA VINCULADA</div>
        <table class="w-100 collapse" style="font-size: 7.5px;">
            <tr>
                <td style="width: 30%;">
                    <span class="text-muted" style="display: block;">Recibo RP Origen:</span>
                    <strong class="text-navy font-mono">{{ $creditNote->origenPago?->numero_recibo_pago ?? 'RP-ORIGEN' }}</strong>
                </td>
                <td style="width: 25%;">
                    <span class="text-muted" style="display: block;">Forma de Pago:</span>
                    <strong class="text-uppercase text-navy">{{ str_replace('_', ' ', $creditNote->origenPago?->metodo_pago ?? 'N/A') }}</strong>
                </td>
                <td style="width: 25%;">
                    <span class="text-muted" style="display: block;">Ref. Bancaria:</span>
                    <strong class="text-navy font-mono">{{ $creditNote->origenPago?->referencia ?? 'S/R' }}</strong>
                </td>
                <td style="width: 20%; text-align: right;">
                    <span class="text-muted" style="display: block;">Monto Total Pagado:</span>
                    <strong class="text-navy font-mono">Bs. {{ number_format((float)($creditNote->origenPago?->monto ?? $creditNote->monto_original), 2, ',', '.') }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="4" style="padding-top: 5px;">
                    <span class="text-muted" style="display: block;">Motivo y Concepto:</span>
                    <span class="text-slate font-bold">{{ $creditNote->motivo ?? 'Excedente de pago registrado a favor del propietario.' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- 5. Historial de Aplicación en Futuros Recibos -->
    <div class="table-title">HISTORIAL DE APLICACIÓN Y CONSUMO EN RECIBOS DE COBRO</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%; text-align: left;">Fecha de Consumo</th>
                <th style="width: 35%; text-align: left;">Recibo(s) / Concepto Financiado</th>
                <th style="width: 20%; text-align: right;">Monto Deducido ($ USD)</th>
                <th style="width: 20%; text-align: right;">Monto Deducido (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $consumos = $creditNote->pagosAplicados ?? collect();
                $totalConsumidoBs = 0;
            @endphp

            @forelse($consumos as $pagoConsumo)
                @php
                    $montoConsumoBs = (float)$pagoConsumo->monto;
                    $totalConsumidoBs += $montoConsumoBs;
                    $montoConsumoUsd = round($montoConsumoBs / (float)($pagoConsumo->tasa_cambio ?: ($creditNote->tasa_cambio ?: 36.50)), 2);
                    $invoicesRel = $pagoConsumo->invoices->isNotEmpty() ? $pagoConsumo->invoices : collect([$pagoConsumo->invoice])->filter();
                @endphp
                <tr>
                    <td class="font-bold text-navy">
                        {{ \Carbon\Carbon::parse($pagoConsumo->fecha_pago)->format('d/m/Y') }}
                    </td>
                    <td>
                        @foreach($invoicesRel as $inv)
                            <div>Recibo <strong>{{ $inv->numero_factura ?? 'RI' }}</strong> ({{ $inv->periodo ?? '-' }})</div>
                        @endforeach
                    </td>
                    <td class="text-right font-bold text-slate font-mono">
                        ${{ number_format($montoConsumoUsd, 2, ',', '.') }} USD
                    </td>
                    <td class="text-right font-bold text-navy font-mono">
                        - Bs. {{ number_format($montoConsumoBs, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted" style="padding: 8px;">
                        Esta Nota de Crédito aún no ha sido aplicada a recibos posteriores. El saldo se encuentra <strong>100% disponible</strong> para futuros pagos.
                    </td>
                </tr>
            @endforelse

            <tr class="totals-row">
                <td colspan="3" class="text-right font-bold text-navy">
                    SALDO RESTANTE DISPONIBLE:
                </td>
                <td class="text-right font-bold text-success font-mono" style="font-size: 9px;">
                    Bs. {{ number_format((float)$creditNote->monto_disponible, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 7px; color: #475569; background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 4px 6px; border-radius: 3px; margin-bottom: 6px;">
        <strong>Nota informativa:</strong> Al generarse nuevas cuotas de mantenimiento mensual o recibos de expensas para este inmueble, el sistema reconocerá y aplicará automáticamente este saldo a favor para amortizar o liquidar las obligaciones pendientes.
    </div>

    <!-- 6. Código QR y Certificación Digital -->
    <div class="seal-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 75px; text-align: center; vertical-align: middle;">
                    @if(!empty($qrCodeBase64))
                        <img src="{{ $qrCodeBase64 }}" width="65" height="65" alt="Código QR" style="border: 1px solid #cbd5e1; padding: 2px; background: #fff; border-radius: 3px;" />
                    @else
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($urlCertificacion ?? url('/certificacion/nota-credito/'.$creditNote->id)) }}" width="65" height="65" alt="Código QR" style="border: 1px solid #cbd5e1; padding: 2px; background: #fff; border-radius: 3px;" />
                    @endif
                </td>
                <td style="vertical-align: middle; padding-left: 8px; text-align: left;">
                    <div style="font-size: 8.5px; font-weight: 800; color: #047857; text-transform: uppercase;">
                        CERTIFICACIÓN DIGITAL DE NOTA DE CRÉDITO EN VIVO
                    </div>
                    <div style="font-size: 7px; color: #475569; margin-top: 2px; line-height: 1.25;">
                        Escanee este código con su teléfono celular para verificar la autenticidad, saldo restante en tiempo real y conciliación contable de esta Nota de Crédito en el sistema.
                    </div>
                    <div style="font-size: 6.5px; color: #64748b; margin-top: 3px; font-family: monospace;">
                        Enlace directo: <strong>{{ $urlCertificacion ?? url('/certificacion/nota-credito/'.$creditNote->id) }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 7. Footer -->
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
