<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Oficial de Pago - {{ $numReciboPago }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
            line-height: 1.5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #064e3b 0%, #0f172a 100%);
            padding: 28px 32px;
            color: #ffffff;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header .subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: #a7f3d0;
        }
        .badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            margin-top: 10px;
            font-family: monospace;
            text-transform: uppercase;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 15px;
            color: #334155;
            margin-bottom: 20px;
        }
        .highlight-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .highlight-card .label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #047857;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .highlight-card .amount-bs {
            font-size: 28px;
            font-weight: 900;
            color: #065f46;
            font-family: monospace;
            line-height: 1.1;
        }
        .highlight-card .amount-usd {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
            margin-top: 6px;
        }
        .highlight-card .status-pill {
            display: inline-block;
            background-color: #059669;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 12px;
            margin-top: 8px;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            margin-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 6px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .details-table td {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .details-table td.label {
            color: #64748b;
            width: 45%;
        }
        .details-table td.value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }
        .invoices-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 24px;
        }
        .invoices-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .invoices-item:last-child {
            border-bottom: none;
        }
        .nc-alert {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #166534;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0 16px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $condominio->nombre }}</h1>
            <div class="subtitle">Comprobante Oficial de Pago Verificado</div>
            <div class="badge">{{ $numReciboPago }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Estimado(a) <strong>{{ $nombreProp }}</strong>,<br>
                Le informamos que su notificación de pago para el <strong>Apartamento {{ $numeroApto }}</strong> ha sido verificada y conciliada exitosamente por la administración.
            </div>

            <!-- Resumen Destacado -->
            <div class="highlight-card">
                <div class="label">Monto Aprobado y Aplicado</div>
                <div class="amount-bs">Bs. {{ $montoBs }}</div>
                <div class="amount-usd">Equivalente: ${{ $montoUsd }} USD (Tasa BCV: Bs. {{ $tasa }})</div>
                <div>
                    <span class="status-pill">✓ Conciliado y Aprobado</span>
                </div>
            </div>

            <!-- Datos de la Transacción -->
            <div class="section-title">Detalles de la Transacción</div>
            <table class="details-table">
                <tr>
                    <td class="label">N° de Recibo Oficial:</td>
                    <td class="value" style="font-family: monospace; color: #047857;">{{ $numReciboPago }}</td>
                </tr>
                <tr>
                    <td class="label">Inmueble / Unidad:</td>
                    <td class="value">Apto. {{ $numeroApto }}</td>
                </tr>
                <tr>
                    <td class="label">Forma de Pago:</td>
                    <td class="value" style="text-transform: capitalize;">{{ str_replace('_', ' ', $metodoPago) }}</td>
                </tr>
                @if(!empty($bancoEmisor))
                <tr>
                    <td class="label">Banco / Origen:</td>
                    <td class="value">{{ $bancoEmisor }}</td>
                </tr>
                @endif
                @if(!empty($referencia))
                <tr>
                    <td class="label">N° de Referencia / Depósito:</td>
                    <td class="value" style="font-family: monospace;">{{ $referencia }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Fecha del Pago:</td>
                    <td class="value">{{ $fechaPago }}</td>
                </tr>
                @if(!empty($canalDestino))
                <tr>
                    <td class="label">Canal Receptor:</td>
                    <td class="value">{{ $canalDestino }}</td>
                </tr>
                @endif
            </table>

            <!-- Desglose de Recibos Cubiertos -->
            @if(!empty($recibosCubiertos) && count($recibosCubiertos) > 0)
            <div class="section-title">Recibos de Cobro Saldados</div>
            <div class="invoices-box">
                @foreach($recibosCubiertos as $item)
                <div class="invoices-item">
                    <span><strong>{{ $item['periodo'] }}</strong> ({{ $item['numero'] }})</span>
                    <span style="font-family: monospace; font-weight: bold; color: #047857;">- Bs. {{ $item['monto_bs'] }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Si se generó Nota de Crédito por sobrepago -->
            @if(!empty($notaCredito))
            <div class="nc-alert">
                <strong>⭐ Saldo a Favor Generado:</strong> Su pago superó el total de la deuda por lo que se generó automáticamente la <strong>Nota de Crédito {{ $notaCredito['numero'] }}</strong> por un monto de <strong>Bs. {{ $notaCredito['monto_bs'] }} (${{ $notaCredito['monto_usd'] }} USD)</strong> disponible para sus próximos recibos.
            </div>
            @endif

            <!-- Botones de Acción -->
            <div class="btn-container">
                <a href="{{ $urlPdfRp }}" class="btn" target="_blank">
                    📄 Descargar Recibo Oficial RP (PDF)
                </a>
            </div>
            <div style="text-align: center; margin-top: 10px;">
                <a href="{{ $urlSistema }}" style="color: #64748b; font-size: 12px; text-decoration: underline;">
                    Ingresar al Portal del Condominio
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $condominio->nombre }}</strong> • RIF: {{ $condominio->rif ?? 'J-00000000-0' }}</p>
            <p>{{ $condominio->direccion ?? 'Administración de Condominio' }}</p>
            <p style="margin-top: 12px; font-size: 11px; color: #94a3b8;">
                Este es un comprobante digital generado automáticamente por el Sistema de Gestión de Condominios.
            </p>
        </div>
    </div>
</body>
</html>
