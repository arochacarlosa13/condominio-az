<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificación de Nota de Crédito - {{ $doc_numero }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between p-4 sm:p-6 antialiased">

    <!-- Top Branding Navigation Bar -->
    <header class="max-w-2xl mx-auto w-full mb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center px-1 gap-2">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-black text-white text-base shadow-sm">
                AZ
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-tight text-slate-900 block leading-none">Sistema de Gestión Inteligente de Condominios • AZPRO</span>
                <span class="text-[10px] font-semibold text-slate-500">Plataforma de Control Financiero y Auditoría Digital</span>
            </div>
        </div>
        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full shadow-sm">
            Validación Digital de Nota de Crédito
        </span>
    </header>

    <div class="max-w-2xl mx-auto w-full space-y-5">

        @if(!$esValido)
            <!-- Alerta de Seguridad por Manipulación de Parámetros/ID en la URL -->
            <div class="bg-white border-2 border-rose-300 rounded-2xl p-6 shadow-md text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-rose-100 text-rose-600 rounded-full border border-rose-200">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 bg-rose-50 text-rose-700 text-[10px] font-extrabold rounded-full uppercase tracking-wider border border-rose-200 mb-1">
                        Error de Verificación Criptográfica
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Nota de Crédito No Válida o Parámetros Alterados
                    </h1>
                    <p class="text-xs text-slate-600 max-w-md mx-auto font-medium mt-2 leading-relaxed">
                        El código token enviado no coincide con la firma digital inalterable de esta Nota de Crédito.
                    </p>
                </div>
            </div>
        @else
            <!-- Header Card de Autenticidad VÁLIDA -->
            <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-200">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-extrabold rounded-full uppercase tracking-wider border border-emerald-200 mb-1">
                        ✓ Nota de Crédito Oficial Certificada
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Nota de Crédito {{ $doc_numero }}
                    </h1>
                    <p class="text-xs text-slate-600 font-medium max-w-lg mx-auto leading-relaxed mt-1">
                        Certificamos la validez del saldo a favor de <strong class="text-slate-900">Bs. {{ number_format($creditNote->monto_disponible, 2, ',', '.') }} (${{ number_format($creditNote->monto_disponible_usd, 2, ',', '.') }} USD)</strong> correspondiente al <strong>Apartamento {{ $apartamento->numero ?? '-' }}</strong>.
                    </p>
                </div>
            </div>

            <!-- Ficha del Condominio e Identificación Documento -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-100 pb-3 gap-2">
                    <div>
                        <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Residencia / Condominio</div>
                        <h2 class="text-base font-bold text-slate-900">{{ $condominio->nombre ?? 'CONDOMINIO RESIDENCIAL' }}</h2>
                        <span class="text-xs text-slate-500 font-mono">RIF: {{ $condominio->rif ?? 'J-300576531' }}</span>
                    </div>
                    <div class="text-left sm:text-right bg-emerald-50 p-2.5 rounded-xl border border-emerald-200">
                        <span class="text-[10px] text-emerald-700 font-bold uppercase tracking-wider block">N° Nota de Crédito</span>
                        <span class="text-sm font-black text-emerald-800 font-mono">{{ $doc_numero }}</span>
                    </div>
                </div>

                <!-- Ficha Inmueble & Propietario -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1">Inmueble / Unidad</span>
                        <div class="font-bold text-slate-900 text-sm">Apartamento {{ $apartamento->numero ?? '-' }}</div>
                        <div class="text-slate-500">Piso {{ $apartamento->piso ?? '-' }}</div>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1">Propietario / Residente</span>
                        <div class="font-bold text-slate-900 text-sm">{{ $propietario->nombre_completo ?? 'Residente' }}</div>
                        <div class="text-slate-500 font-mono">CI/RIF: {{ $propietario->cedula_rif ?? 'V-00000000' }}</div>
                    </div>
                </div>
            </div>

            <!-- Saldos Financieros -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        <span class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Monto Original Acreditado</span>
                        <div class="text-lg font-black text-slate-800 font-mono">Bs. {{ number_format((float)$creditNote->monto_original, 2, ',', '.') }}</div>
                        <span class="text-xs text-slate-600 font-bold font-mono">${{ number_format((float)$creditNote->monto_original_usd, 2, ',', '.') }} USD</span>
                    </div>
                    <div class="bg-emerald-50 p-3.5 rounded-xl border border-emerald-300">
                        <span class="text-[10px] text-emerald-800 uppercase font-bold block mb-1">Saldo Restante Disponible</span>
                        <div class="text-lg font-black text-emerald-700 font-mono">Bs. {{ number_format((float)$creditNote->monto_disponible, 2, ',', '.') }}</div>
                        <span class="text-xs text-emerald-800 font-bold font-mono">${{ number_format((float)$creditNote->monto_disponible_usd, 2, ',', '.') }} USD</span>
                    </div>
                </div>
            </div>

            <!-- Botón para Descargar PDF -->
            <div class="text-center pt-2">
                <a href="/api/v1/reportes/nota-credito/{{ $creditNote->id }}" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Descargar Documento Oficial PDF
                </a>
            </div>
        @endif

    </div>

    <!-- Footer -->
    <footer class="max-w-2xl mx-auto w-full text-center text-xs text-slate-400 py-6">
        © 2026 AZPRO Condominios SaaS • Todos los derechos reservados.
    </footer>

</body>
</html>
