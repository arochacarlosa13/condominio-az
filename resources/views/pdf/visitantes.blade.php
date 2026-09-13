<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Visitantes</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #333; }
        .title { font-size: 18px; font-weight: bold; color: #1A237E; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #CBD5E1; padding: 6px 8px; text-align: left; }
        th { background-color: #F1F5F9; color: #1E293B; }
    </style>
</head>
<body>
    <div class="title">{{ $condominio->nombre }} - Bitácora de Control de Acceso y Visitantes</div>
    <div>Fecha de reporte: {{ date('d/m/Y') }}</div>

    <table>
        <thead>
            <tr>
                <th>Fecha y Hora Entrada</th>
                <th>Nombre del Visitante</th>
                <th>Cédula</th>
                <th>Unidad Destino</th>
                <th>Placa</th>
                <th>Hora Salida</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visitantes as $v)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($v->hora_entrada)->format('d/m/Y h:i A') }}</td>
                    <td>{{ $v->nombre_completo }}</td>
                    <td>{{ $v->cedula }}</td>
                    <td>Apto. {{ $v->apartamento?->numero }}</td>
                    <td>{{ $v->placa_vehiculo ?? 'N/A' }}</td>
                    <td>{{ $v->hora_salida ? \Carbon\Carbon::parse($v->hora_salida)->format('d/m/Y h:i A') : 'En instalaciones' }}</td>
                    <td>{{ $v->motivo ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">No hay registros de visitantes para el período seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
