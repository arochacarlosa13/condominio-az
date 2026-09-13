<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Propietarios y Residentes - {{ $condominio->nombre ?? 'Condominio' }}</title>
    <style>
        @page {
            margin: 15px 20px 35px 20px;
        }
        footer {
            position: fixed;
            bottom: -25px;
            left: 0px;
            right: 0px;
            height: 22px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            padding: 2px 6px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
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
        .text-muted { color: #64748b; }
        .text-primary { color: #2563eb; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
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
            background-color: #f8fafc;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: right;
            border-radius: 4px;
        }
        .doc-type-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .summary-cell {
            padding: 5px 8px;
            border-right: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .summary-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }
        .summary-val {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            padding: 4px 5px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 3.5px 5px;
            font-size: 8px;
            border-bottom: 1px solid #cbd5e1;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .row-alt { background-color: #f8fafc; }

        .chip-status-active {
            display: inline-block;
            background-color: #dcfce7;
            color: #166534;
            font-size: 7px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .chip-status-inactive {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            font-size: 7px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .chip-unit {
            display: inline-block;
            background-color: #ede9fe;
            color: #5b21b6;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
        }
    </style>
</head>
<body>

<!-- Encabezado Principal -->
<table class="header-table">
    <tr>
        <td style="width: 65%; vertical-align: top;">
            <div class="condo-title">{{ mb_strtoupper($condominio->nombre ?? 'AZPRO - GESTIÓN DE CONDOMINIOS') }}</div>
            <div style="margin-top: 2px;">
                <span class="rif-badge">RIF: {{ $condominio->rif ?? 'J-00000000-0' }}</span>
                <span style="font-size: 8px; color: #475569; margin-left: 5px;">{{ $condominio->direccion ?? 'Dirección Fiscal' }}</span>
            </div>
            <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">
                Teléfono: {{ $condominio->telefono ?? 'N/A' }} | Email: {{ $condominio->email ?? 'contacto@condominio.com' }}
            </div>
        </td>
        <td style="width: 35%; vertical-align: top;">
            <div class="doc-badge-box">
                <div class="doc-type-title">CENSO OFICIAL DE PROPIETARIOS</div>
                <div style="font-size: 8px; color: #475569; margin-top: 2px;">
                    Fecha de Emisión: <strong>{{ date('d/m/Y h:i A') }}</strong>
                </div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">
                    Emitido por: {{ auth()->user()?->name ?? 'Administración' }}
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- Ficha Resumen de Estadísticas -->
<table class="summary-grid">
    <tr>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">TOTAL PROPIETARIOS / RESIDENTES</div>
            <div class="summary-val text-primary">{{ count($propietarios) }} Registrados</div>
        </td>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">CUENTAS ACTIVAS CON ACCESO</div>
            <div class="summary-val text-success">{{ $totalActivos ?? count($propietarios) }} Usuarios</div>
        </td>
        <td class="summary-cell" style="width: 25%;">
            <div class="summary-label">INMUEBLES ASIGNADOS</div>
            <div class="summary-val text-navy">{{ $totalConApto ?? 0 }} Unidades</div>
        </td>
        <td class="summary-cell" style="width: 25%; border-right: none;">
            <div class="summary-label">SUMA ÁREA PRIVADA (M²)</div>
            <div class="summary-val text-navy">{{ number_format($totalMetros ?? 0, 2) }} m²</div>
        </td>
    </tr>
</table>

<!-- Tabla de Propietarios -->
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 3%; text-align: center;">#</th>
            <th style="width: 10%; text-align: center;">Cédula / RIF</th>
            <th style="width: 20%; text-align: left;">Nombre y Apellidos</th>
            <th style="width: 18%; text-align: left;">Correo Electrónico</th>
            <th style="width: 11%; text-align: left;">Teléfono(s)</th>
            <th style="width: 12%; text-align: center;">Inmueble / Piso</th>
            <th style="width: 7%; text-align: right;">Área (m²)</th>
            <th style="width: 7%; text-align: right;">Alícuota</th>
            <th style="width: 12%; text-align: center;">Condominio / Torre</th>
        </tr>
    </thead>
    <tbody>
        @forelse($propietarios as $idx => $p)
            @php
                $apto = $p->apartamento;
                $condoNombre = $p->condominio?->nombre ?? ($apto?->condominio?->nombre ?? 'Principal');
                $torre = $p->condominio?->torre_bloque ?? $apto?->condominio?->torre_bloque;
            @endphp
            <tr class="{{ $idx % 2 == 1 ? 'row-alt' : '' }}">
                <td class="text-center font-bold" style="color: #64748b;">{{ $idx + 1 }}</td>
                <td class="text-center font-bold text-navy">{{ $p->cedula ?: 'S/C' }}</td>
                <td class="font-bold text-navy">
                    {{ $p->name }}
                    @if(!empty($p->bloqueado_hasta) && \Carbon\Carbon::parse($p->bloqueado_hasta)->isFuture())
                        <span class="chip-status-inactive">Bloqueado</span>
                    @endif
                </td>
                <td style="color: #334155;">{{ $p->email }}</td>
                <td>{{ $p->telefono ?: 'No registrado' }}</td>
                <td class="text-center">
                    @if($apto)
                        <span class="chip-unit">Apto {{ $apto->numero }} (Piso {{ $apto->piso }})</span>
                    @else
                        <span class="text-muted italic">Sin asignar</span>
                    @endif
                </td>
                <td class="text-right font-bold">
                    {{ $apto ? number_format((float)$apto->metros_cuadrados, 2) . ' m²' : '-' }}
                </td>
                <td class="text-right font-bold text-navy">
                    {{ $apto ? number_format((float)($apto->alicuota ?? 0), 4) . '%' : '-' }}
                </td>
                <td class="text-center" style="font-size: 7.5px;">
                    {{ $condoNombre }}
                    @if($torre)
                        <span class="text-muted">({{ $torre }})</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center text-muted" style="padding: 15px;">
                    No se encontraron propietarios o residentes registrados para los criterios seleccionados.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Footer Fijo -->
<footer>
    <div style="font-size: 7px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase;">
        REPORTE OFICIAL DE PROPIETARIOS Y CENSO DE RESIDENTES • AZPRO SISTEMA DE GESTIÓN INTELIGENTE DE CONDOMINIOS
    </div>
    <div style="font-size: 6.5px; color: #64748b; margin-top: 1px; font-style: italic;">
        Documento confidencial para uso administrativo y control interno de la comunidad. No incluye contraseñas ni claves de acceso.
    </div>
</footer>

</body>
</html>
