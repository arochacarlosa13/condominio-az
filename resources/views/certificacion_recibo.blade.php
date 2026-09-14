<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificación Criptográfica de Recibo - {{ $doc_numero }}</title>
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
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center font-black text-white text-base shadow-sm">
                AZ
            </div>
            <div>
                <span class="font-extrabold text-sm tracking-tight text-slate-900 block leading-none">Sistema de Gestión Inteligente de Condominios • AZPRO</span>
                <span class="text-[10px] font-semibold text-slate-500">Sellado Criptográfico SHA-256 y Verificación Inmutable</span>
            </div>
        </div>
        <span class="text-[11px] font-bold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-sm">
            Portal de Validación Digital
        </span>
    </header>

    <div class="max-w-2xl mx-auto w-full space-y-5">

        @if(!$esValido)
            <!-- Alerta de Seguridad por Manipulación -->
            <div class="bg-white border-2 border-rose-300 rounded-2xl p-6 shadow-md text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-rose-100 text-rose-600 rounded-full border border-rose-200">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 bg-rose-50 text-rose-700 text-[10px] font-extrabold rounded-full uppercase tracking-wider border border-rose-200 mb-1">
                        Firma Criptográfica No Válida
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Documento No Auténtico o Parámetros Modificados
                    </h1>
                    <p class="text-xs text-slate-600 max-w-md mx-auto font-medium mt-2 leading-relaxed">
                        El token digital proporcionado no coincide con el hash criptográfico SHA-256 generado al momento de emitir este recibo.
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
                        ✓ Documento Auténtico y Certificado
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Recibo Oficial de Condominio Certificado
                    </h1>
                    <p class="text-xs text-slate-600 font-medium max-w-lg mx-auto leading-relaxed mt-1">
                        Certificamos la validez legal del recibo N° <strong class="text-slate-900">{{ $doc_numero }}</strong> correspondiente al período <strong class="text-slate-900">{{ $invoice->periodo }}</strong> emitido por el condominio <strong class="text-slate-900">{{ $condominio->nombre ?? 'Condominio' }}</strong>.
                    </p>
                </div>
            </div>

            <!-- Ficha del Condominio e Identificación Documento -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-100 pb-3 gap-2">
                    <div>
                        <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Condominio / Edificio</div>
                        <h2 class="text-base font-bold text-slate-900">{{ $condominio->nombre ?? 'CONDOMINIO RESIDENCIAL' }}</h2>
                        <div class="text-xs text-slate-500 font-medium">RIF: {{ $condominio->rif ?? 'J-00000000-0' }}</div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="inline-block px-2.5 py-0.5 {{ $invoice->estaCertificado() ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-amber-50 text-amber-700 border-amber-200' }} border text-[11px] font-extrabold rounded-md font-mono">
                            {{ $invoice->estaCertificado() ? 'CERTIFICADO & BLOQUEADO' : 'DOCUMENTO BORRADOR' }}
                        </span>
                        <div class="text-[11px] font-bold text-slate-700 font-mono mt-0.5">N° {{ $doc_numero }}</div>
                    </div>
                </div>

                <!-- Datos del Inmueble y Propietario -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-medium block text-[10px] uppercase">Unidad Inmobiliaria</span>
                        <span class="font-bold text-slate-800 text-sm">Apartamento {{ $apartamento->numero ?? 'N/A' }}</span>
                        <span class="text-slate-500 block">Piso {{ $apartamento->piso ?? '-' }} • Alícuota: {{ number_format($apartamento->alicuota ?? 0, 4) }}%</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-medium block text-[10px] uppercase">Copropietario / Titular</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $propietario->nombre_completo ?? ($propietario->name ?? 'Propietario Registrado') }}</span>
                        <span class="text-slate-500 block">C.I. / RIF: {{ $propietario->cedula ?? 'N/D' }}</span>
                    </div>
                </div>

                <!-- Importes Oficiales -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Importe Facturado</span>
                        <div class="text-2xl font-black text-slate-900 font-mono">
                            ${{ number_format($invoice->monto_total_usd, 2, '.', '') }} USD
                        </div>
                        <div class="text-xs font-bold text-emerald-700 font-mono">
                            ≈ Bs. {{ number_format($invoice->monto_total, 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-left sm:text-right text-xs text-slate-600 font-mono">
                        <div>Tasa Oficial BCV: <strong>Bs. {{ number_format($invoice->tasa_cambio, 2, ',', '.') }}</strong></div>
                        <div>Fecha Emisión: <strong>{{ $invoice->fecha_emision ? \Carbon\Carbon::parse($invoice->fecha_emision)->format('d/m/Y') : 'N/D' }}</strong></div>
                        <div>Estado de Pago: <strong class="text-uppercase text-indigo-700">{{ $invoice->estado }}</strong></div>
                    </div>
                </div>

                <!-- Sello Criptográfico SHA-256 -->
                <div class="p-3 bg-slate-900 text-white rounded-xl font-mono text-[10px] break-all space-y-1">
                    <div class="text-slate-400 font-bold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        FIRMA DIGITAL INMUTABLE SHA-256:
                    </div>
                    <div class="text-emerald-300 select-all">
                        {{ $hashSha256 }}
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Footer -->
    <footer class="max-w-2xl mx-auto w-full text-center text-[10px] text-slate-400 mt-6 pb-2">
        AZPRO • Sistema de Gestión de Condominios de Alto Rendimiento. Verificación Criptográfica Inmutable en Tiempo Real.
    </footer>

</body>
</html>
