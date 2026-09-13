<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo General del Edificio - {{ $periodo_nombre ?? 'Período' }}</title>
    <style>
        @page {
            margin: 15px 20px 45px 20px;
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
            font-size: 9px;
            color: #1e293b;
            line-height: 1.3;
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

        .text-navy { color: #1e293b; }
        .text-muted { color: #64748b; }
        .text-primary { color: #334155; }
        .text-success { color: #15803d; }
        .bg-navy { background-color: #f1f5f9; color: #0f172a; }
        .bg-slate { background-color: #f1f5f9; }
        .bg-light { background-color: #f8fafc; }
        .border-slate { border: 1px solid #cbd5e1; }

        .header-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 6px;
        }
        .condo-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .rif-badge {
            display: inline-block;
            background-color: #e2e8f0;
            color: #334155;
            padding: 1px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 2px;
        }
        .doc-badge-box {
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: right;
            border-radius: 4px;
        }
        .doc-type-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #0f172a;
        }
        .doc-period-title {
            font-size: 11px;
            font-weight: 800;
            color: #334155;
        }

        .watermark-borrador {
            position: fixed;
            top: 35%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 48px;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.15);
            transform: rotate(-30deg);
            z-index: 1000;
            text-transform: uppercase;
            letter-spacing: 4px;
            border: 5px dashed rgba(239, 68, 68, 0.2);
            padding: 20px;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .summary-cell {
            padding: 6px 8px;
            border-right: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .summary-label {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }
        .summary-val {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }

        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .expenses-table th {
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 4px 6px;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
        }
        .expenses-table td {
            padding: 3px 6px;
            font-size: 8.5px;
            border-bottom: 1px solid #cbd5e1;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }
        .row-alt { background-color: #f8fafc; }

        .totals-bar {
            background-color: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }

        .legal-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 7.5px;
            color: #334155;
            line-height: 1.3;
            margin-top: 10px;
        }
    </style>
</head>
<body>

@if(!empty($es_borrador))
    <div class="watermark-borrador">
        BORRADOR PRELIMINAR<br>
        <span style="font-size: 20px;">DOCUMENTO DE MUESTRA NO CERTIFICADO</span>
    </div>
@endif

<!-- Encabezado Principal del Condominio -->
<table class="header-card">
    <tr>
        <td style="width: 65%; vertical-align: top;">
            <div class="condo-title">{{ mb_strtoupper($condominio->nombre ?? 'CONDOMINIO TORREVIVA') }}</div>
            <div style="margin-top: 2px;">
                <span class="rif-badge">RIF: {{ $condominio->rif ?? 'J-12345678-9' }}</span>
                <span style="font-size: 8px; color: #475569; margin-left: 5px;">{{ $condominio->direccion ?? 'Dirección del Condominio' }}</span>
            </div>
            <div style="font-size: 7.5px; color: #64748b; margin-top: 3px;">
                Teléfono: {{ $condominio->telefono ?? 'N/A' }} | Email: {{ $condominio->email ?? 'contacto@condominio.com' }}
            </div>
        </td>
        <td style="width: 35%; vertical-align: top;">
            <div class="doc-badge-box">
                <div class="doc-type-title">{{ !empty($es_extraordinario) ? 'RECIBO GENERAL - CUOTA EXTRAORDINARIA' : 'RECIBO GENERAL DEL EDIFICIO' }}</div>
                <div class="doc-period-title">{{ mb_strtoupper($periodo_nombre ?? 'PERÍODO') }}</div>
                @if(!empty($es_extraordinario) && !empty($titulo_proyecto))
                    <div style="font-size: 7.5px; font-weight: bold; color: #6d28d9; margin-top: 2px;">
                        PROYECTO: {{ mb_strtoupper($titulo_proyecto) }}
                    </div>
                @endif
                <div style="font-size: 8px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                    N° DOC: <strong>{{ $doc_numero ?? 'RG2026-00001' }}</strong>
                </div>
                <div style="font-size: 7.5px; color: #484747; margin-top: 2px;">
                    Fecha de Emisión: {{ $fecha_emision ?? date('Y-m-d') }}
                </div>
                <div style="font-size: 7.5px; margin-top: 2px;">
                    Estado: <strong style="color: {{ !empty($es_borrador) ? '#f59e0b' : '#34d399' }};">{{ !empty($es_borrador) ? 'BORRADOR' : 'CERTIFICADO' }}</strong>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- Ficha de Resumen del Presupuesto del Condominio -->
<table class="summary-grid">
    <tr>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">PRESUPUESTO TOTAL USD</div>
            <div class="summary-val text-primary">${{ number_format($total_gastos_usd ?? 0, 2) }} USD</div>
        </td>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">TOTAL EN BOLÍVARES (BS.)</div>
            <div class="summary-val text-success">Bs. {{ number_format($total_gastos_bs ?? 0, 2, ',', '.') }}</div>
        </td>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">TASA DE CAMBIO BCV</div>
            <div class="summary-val">Bs. {{ number_format($tasa_bcv ?? 36.50, 2, ',', '.') }}</div>
        </td>
        <td class="summary-cell" style="width: 25%; border-right: none;">
            <div class="summary-label">APARTAMENTOS TOTALES</div>
            <div class="summary-val">{{ $total_apartamentos ?? 0 }} Inmuebles</div>
        </td>
    </tr>
</table>

<!-- Tabla de Relación de Gastos Comunes del Edificio -->
<div style="font-size: 9px; font-weight: 800; color: #1e293b; text-transform: uppercase; margin-bottom: 4px;">
    {{ !empty($es_extraordinario) ? 'Relación Consolidada de Conceptos del Proyecto Extraordinario:' : 'Relación Consolidada de Gastos Ejecutados en el Mes:' }}
</div>

<table class="expenses-table">
    <thead>
        <tr>
            <th style="width: 5%; text-align: center;">#</th>
            <th style="width: 7%; text-align: center;">Ali</th>
            <th style="width: 58%; text-align: left;">Concepto de Gasto / Descripción</th>
            <th style="width: 15%; text-align: right;">Monto ($ USD)</th>
            <th style="width: 15%; text-align: right;">Monto (Bs.)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $sumUsd = 0;
            $tasaVal = (float)($tasa_bcv ?? 36.50);
        @endphp
        @forelse($gastos as $idx => $g)
            @php
                $montoItem = (float)($g['monto'] ?? 0);
                $sumUsd += $montoItem;
                $montoItemBs = round($montoItem * $tasaVal, 2);
            @endphp
            <tr class="{{ $idx % 2 == 1 ? 'row-alt' : '' }}">
                <td class="text-center font-bold" style="color: #64748b;">{{ $idx + 1 }}</td>
                <td class="text-center font-bold">Ali {{ $g['ali'] ?? '1' }}</td>
                <td class="font-bold text-navy">{{ $g['concepto'] }}</td>
                <td class="text-right font-bold">${{ number_format($montoItem, 2) }}</td>
                <td class="text-right text-muted">Bs. {{ number_format($montoItemBs, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 10px;">
                    No hay conceptos de gasto registrados para este período.
                </td>
            </tr>
        @endforelse

        @if(empty($es_extraordinario) && !empty($fondo_mes_monto) && $fondo_mes_monto > 0)
            @php
                $sumUsd += $fondo_mes_monto;
                $fondoBs = round($fondo_mes_monto * $tasaVal, 2);
            @endphp
            <tr style="background-color: #fffbeb;">
                <td class="text-center font-bold" style="color: #b45309;">-</td>
                <td class="text-center font-bold" style="color: #b45309;">Fondo</td>
                <td class="font-bold" style="color: #92400e;">FONDO DE RESERVA DEL MES ({{ $fondo_porcentaje ?? 10 }}%)</td>
                <td class="text-right font-bold" style="color: #92400e;">${{ number_format($fondo_mes_monto, 2) }}</td>
                <td class="text-right font-bold" style="color: #92400e;">Bs. {{ number_format($fondoBs, 2, ',', '.') }}</td>
            </tr>
        @endif
    </tbody>
    <tfoot>
        <tr class="totals-bar">
            <td colspan="3" class="text-right font-bold" style="padding: 5px;">
                {{ !empty($es_extraordinario) ? 'TOTAL GENERAL PRESUPUESTO EXTRAORDINARIO:' : 'TOTAL GENERAL PRESUPUESTO EDIFICIO:' }}
            </td>
            <td class="text-right font-bold text-primary" style="padding: 5px; font-size: 10px;">${{ number_format($sumUsd, 2) }} USD</td>
            <td class="text-right font-bold text-success" style="padding: 5px; font-size: 10px;">Bs. {{ number_format(round($sumUsd * $tasaVal, 2), 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<!-- Notas y Cláusulas Legales del Recibo General -->
<div class="legal-box">
    <strong>NOTAS GENERALES DEL CONDOMINIO:</strong><br>
    @if(!empty($es_extraordinario))
        Este reporte representa la relación total consolidada del presupuesto correspondiente a la cuota extraordinaria {{ $periodo_nombre }}.
        La distribución del cobro a cada copropietario se realiza a partes iguales. Este documento está exento de Fondo de Reserva (0%).
    @else
        Este reporte representa la relación total consolidada de gastos comunes correspondientes al período {{ $periodo_nombre }}.
        Los montos individuales asignados a cada inmueble se calculan multiplicando el valor por su alícuota correspondiente.
    @endif
    {{ $condominio->notas_recibo ?? 'Para cualquier consulta o aclaratoria, diríjase a la oficina de administración.' }}
</div>

<!-- Banner de Marketing e Identidad Fijo al Final de la Hoja -->
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
