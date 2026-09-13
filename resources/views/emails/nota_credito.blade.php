<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Crédito a su Favor - {{ $notaCredito->numero_nota_credito }}</title>
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
            color: #6ee7b7;
        }
        .badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #a7f3d0;
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
            color: #065f46;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .highlight-card .amount-usd {
            font-size: 30px;
            font-weight: 900;
            color: #047857;
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
            color: #047857;
            margin-top: 4px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .details-table tr td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .details-table tr td:first-child {
            color: #64748b;
            font-weight: 600;
            width: 40%;
        }
        .details-table tr td:last-child {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #10b981;
            padding: 14px 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0 16px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 32px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $condominio->nombre }}</h1>
            <div class="subtitle">Gestión Inteligente y Automatizada de Condominios</div>
            <div class="badge">Nota de Crédito • Saldo a Favor</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Estimado(a) Propietario(a) <strong>{{ $nombreProp }}</strong>,<br>
                Le informamos que tras la verificación y aprobación de su pago reciente, se ha registrado un <strong>excedente a su favor</strong> correspondiente al <strong>Apartamento {{ $numeroApto }}</strong>.
            </div>

            <!-- Highlight Card -->
            <div class="highlight-card">
                <div class="label">Saldo a Favor Acreditado</div>
                <div class="amount-usd">${{ $montoUsd }} USD</div>
                <div class="amount-bs">Bs. {{ $montoBs }}</div>
                <div class="rate-info">Tasa de emisión: Bs. {{ $tasa }} / USD</div>
            </div>

            <!-- Details Table -->
            <table class="details-table">
                <tr>
                    <td>N° Nota de Crédito:</td>
                    <td><code style="color: #059669; font-weight: bold;">{{ $notaCredito->numero_nota_credito }}</code></td>
                </tr>
                <tr>
                    <td>Fecha de Emisión:</td>
                    <td>{{ \Carbon\Carbon::parse($notaCredito->fecha_emision)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Inmueble:</td>
                    <td>Apartamento {{ $numeroApto }}</td>
                </tr>
                <tr>
                    <td>Recibo de Pago Origen:</td>
                    <td>{{ $reciboPagoNumero }}</td>
                </tr>
                <tr>
                    <td>Estado del Crédito:</td>
                    <td><span style="color: #059669; text-transform: uppercase; font-weight: 800;">✓ DISPONIBLE</span></td>
                </tr>
            </table>

            <!-- Explanatory Box -->
            <div class="info-box">
                <strong>¿Cómo se aplicará su saldo a favor?</strong><br>
                Este excedente queda registrado en su cuenta de copropietario. Al momento en que la administración genere su próximo recibo de expensas o cuota de mantenimiento mensual, <strong>el sistema reconocerá automáticamente este crédito a su favor y generará una notificación de pago descontando este saldo</strong> hasta cubrirlo total o parcialmente.
            </div>

            <!-- Button -->
            <div class="btn-container">
                <a href="{{ $urlSistema }}" class="btn">Ingresar al Portal de Propietarios</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Este es un correo automático generado por el Sistema Inteligente de Gestión de Condominios AZPRO para {{ $condominio->nombre }}.<br>
            © {{ date('Y') }} AZPRO Condominios SaaS. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
