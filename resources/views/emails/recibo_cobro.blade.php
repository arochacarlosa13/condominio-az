<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Cobro Condominio - {{ $periodo }}</title>
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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
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
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #93c5fd;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
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
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .highlight-card .label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e40af;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .highlight-card .amount-usd {
            font-size: 28px;
            font-weight: 900;
            color: #1d4ed8;
            font-family: monospace;
            line-height: 1.1;
        }
        .highlight-card .amount-bs {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
            margin-top: 6px;
        }
        .highlight-card .rate-info {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .details-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .details-table td:last-child {
            text-align: right;
            font-weight: 700;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .bank-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .bank-card strong {
            color: #0f172a;
            font-size: 13px;
            display: block;
            margin-bottom: 3px;
        }
        .btn-primary {
            display: block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 24px;
            border-radius: 10px;
            margin: 24px 0 16px 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .pdf-links {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            text-align: center;
        }
        .pdf-btn {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155 !important;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            margin: 0 4px 8px 4px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 32px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ strtoupper($condominio->nombre) }}</h1>
            <div class="subtitle">RIF: {{ $condominio->rif ?? 'J-300576531' }} • {{ $condominio->direccion ?? 'Av. Principal' }}</div>
            <div class="badge">Período: {{ $periodo }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Estimado(a) <strong>{{ $prop->nombre_completo }}</strong>,<br>
                Le informamos que ha sido emitido el <strong>Recibo de Cobro de Condominio</strong> para su inmueble:
            </div>

            <!-- Highlight Card -->
            <div class="highlight-card">
                <div class="label">Apartamento {{ $apartamento->numero }} • Total a Saldar</div>
                <div class="amount-usd">${{ $montoUsd }} USD</div>
                <div class="amount-bs">Equivalente: Bs. {{ $montoBs }}</div>
                <div class="rate-info">(Tasa Oficial BCV: Bs. {{ $tasa }} / USD)</div>
            </div>

            <!-- Summary Table -->
            <div class="section-title">Resumen del Recibo (RI)</div>
            <table class="details-table">
                <tr>
                    <td style="color: #64748b;">N° de Factura / Recibo:</td>
                    <td style="font-family: monospace; color: #0f172a;">{{ $inv->numero_factura ?? 'RI-'.$inv->id }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b;">Alícuota Inmueble:</td>
                    <td>{{ number_format($apartamento->alicuota > 0 ? $apartamento->alicuota : 3.0346, 4) }}%</td>
                </tr>
                <tr>
                    <td style="color: #64748b;">Cuota Común del Mes:</td>
                    <td>${{ number_format($inv->monto_alicuota_usd ?? 0, 2) }} USD</td>
                </tr>
                @if(!empty($inv->fondos['monto_alicuota']))
                <tr>
                    <td style="color: #64748b;">Aporte Fondo de Reserva:</td>
                    <td>${{ number_format($inv->fondos['monto_alicuota'], 2) }} USD</td>
                </tr>
                @endif
                <tr>
                    <td style="color: #64748b; font-weight: bold;">Monto Total Facturado:</td>
                    <td style="color: #1d4ed8; font-size: 14px;">${{ $montoUsd }} USD / Bs. {{ $montoBs }}</td>
                </tr>
            </table>

            <!-- Cuentas Bancarias -->
            <div class="section-title">Cuentas Bancarias para Realizar el Pago</div>
            @if($cuentasBancarias && $cuentasBancarias->count())
                @foreach($cuentasBancarias as $cta)
                    <div class="bank-card">
                        <strong>{{ $cta->banco_nombre }}</strong>
                        @if($cta->es_pago_movil)
                            <div>📱 <strong>Pago Móvil:</strong> Teléfono: <code>{{ $cta->telefono_pago_movil }}</code> | C.I/RIF: <code>{{ $cta->rif_titular }}</code></div>
                        @endif
                        @if($cta->numero_cuenta)
                            <div>🏦 <strong>Transferencia:</strong> Cta: <code>{{ $cta->numero_cuenta }}</code></div>
                        @endif
                        <div style="color: #64748b; font-size: 11px;">Titular: {{ $cta->titular_nombre }} ({{ $cta->rif_titular }})</div>
                    </div>
                @endforeach
            @else
                <div class="bank-card">
                    <strong>{{ $condominio->banco_nombre ?? 'Banco Nacional de Crédito (BNC)' }}</strong>
                    <div>🏦 Cta Corriente: <code>{{ $condominio->cuenta_bancaria_bs ?? '0191-0514-8221-0001-8351' }}</code></div>
                    <div>📱 Pago Móvil: <code>{{ $condominio->telefono ?? '0414-0000000' }}</code> | RIF: <code>{{ $condominio->rif ?? 'J-300576531' }}</code></div>
                    <div style="color: #64748b; font-size: 11px;">Titular: {{ $condominio->nombre }}</div>
                </div>
            @endif

            <!-- Botón Principal -->
            <a href="{{ $urlSistema }}" class="btn-primary">
                👉 Ingresar al Sistema y Reportar Mi Pago
            </a>

            <!-- Descargas PDF -->
            <div style="text-align: center; margin-top: 10px;">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">O descargue directamente los comprobantes oficiales en PDF:</div>
                <a href="{{ $urlPdfRi }}" target="_blank" class="pdf-btn">📄 Descargar Mi Recibo (RI en PDF)</a>
                <a href="{{ $urlPdfRg }}" target="_blank" class="pdf-btn">📊 Descargar Recibo General (RG en PDF)</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Mensaje generado automáticamente por el <strong>Sistema Inteligente de Gestión de Condominios • AZ PRO</strong></div>
            <div style="margin-top: 4px;">Para dudas o aclaratorias contáctese con la administración del condominio al {{ $condominio->telefono ?? '0414-0000000' }}.</div>
        </div>
    </div>
</body>
</html>
