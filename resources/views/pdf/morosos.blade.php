<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Morosidad</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #333; }
        .title { font-size: 18px; font-weight: bold; color: #1A237E; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #CBD5E1; padding: 6px 8px; text-align: left; }
        th { background-color: #F1F5F9; color: #1E293B; }
    </style>
</head>
<body>
    <div class="title">{{ $condominio->nombre }} - Reporte de Morosidad y Cuentas por Cobrar</div>
    <div>Fecha de emisión: {{ date('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th>Unidad</th>
                <th>Propietario</th>
                <th>Factura</th>
                <th>Vencimiento</th>
                <th>Monto Bs.</th>
                <th>Abonado Bs.</th>
                <th>Saldo Deudor Bs.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($morosos as $m)
                <tr>
                    <td>Apto. {{ $m->apartamento?->numero }}</td>
                    <td>{{ $m->apartamento?->propietarios->first()?->nombre_completo ?? 'N/A' }}</td>
                    <td>{{ $m->numero_factura }}</td>
                    <td>{{ \Carbon\Carbon::parse($m->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td>Bs. {{ number_format($m->monto_total, 2, ',', '.') }}</td>
                    <td>Bs. {{ number_format($m->monto_pagado, 2, ',', '.') }}</td>
                    <td style="color: #DC2626; font-weight: bold;">Bs. {{ number_format($m->monto_total - $m->monto_pagado, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">No hay cuentas pendientes. El condominio se encuentra solvente.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
