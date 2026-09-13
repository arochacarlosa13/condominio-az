<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificación de Autenticidad - {{ $doc_numero }}</title>
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
                <span class="text-[10px] font-semibold text-slate-500">Plataforma de Control Financiero y Auditoría Digital</span>
            </div>
        </div>
        <span class="text-[11px] font-bold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-sm">
            Portal de Verificación Digital
        </span>
    </header>

    <div class="max-w-2xl mx-auto w-full space-y-5">

        @if(!$esValido)
            <!-- Alerta de Seguridad por Manipulación de Parámetros/ID en la URL (Light Mode) -->
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
                        Comprobante No Válido o Parámetros Alterados
                    </h1>
                    <p class="text-xs text-slate-600 max-w-md mx-auto font-medium mt-2 leading-relaxed">
                        El código token enviado no coincide con la firma digital inalterable de la transacción. Si intentó modificar el número de recibo en la URL, la autenticidad es rechazada automáticamente por el sistema.
                    </p>
                </div>
            </div>
        @else
            <!-- Header Card de Autenticidad VÁLIDA (Light Mode Impreso Sobrio) -->
            <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm text-center space-y-3">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-200">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-extrabold rounded-full uppercase tracking-wider border border-emerald-200 mb-1">
                        ✓ Documento Auténtico Certificado
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">
                        Comprobante RP de Pago Verificado
                    </h1>
                    <p class="text-xs text-slate-600 font-medium max-w-lg mx-auto leading-relaxed mt-1">
                        Certificamos que el pago N° <strong class="text-slate-900">{{ $doc_numero }}</strong> por la cantidad de <strong class="text-slate-900">Bs. {{ number_format($payment->monto, 2, ',', '.') }}</strong> fue auditado e inscrito oficialmente en la contabilidad del condominio.
                    </p>
                </div>
            </div>

            <!-- Ficha del Condominio e Identificación Documento -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-100 pb-3 gap-2">
                    <div>
                        <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Residencia / Condominio</div>
                        <h2 class="text-base font-bold text-slate-900">{{ $condominio->nombre ?? 'CONDOMINIO RESIDENCIAL' }}</h2>
                        <span class="text-xs text-slate-500 font-mono">RIF: {{ $condominio->rif ?? 'J-300576531' }}</span>
                    </div>
                    <div class="text-left sm:text-right bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">N° Recibo RP</span>
                        <span class="text-sm font-black text-indigo-600 font-mono">{{ $doc_numero }}</span>
                    </div>
                </div>

                <!-- Ficha Inmueble & Propietario -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1">Inmueble / Unidad</span>
                        <div class="font-bold text-slate-900 text-sm">Apartamento {{ $apartamento->numero ?? '-' }}</div>
                        <div class="text-slate-500">Piso {{ $apartamento->piso ?? '-' }} • Alícuota {{ number_format($alicuota_percent ?? 3.03, 4, ',', '.') }}%</div>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1">Propietario / Residente</span>
                        <div class="font-bold text-slate-900 text-sm">{{ $propietario->nombre_completo ?? 'Residente' }}</div>
                        <div class="text-slate-500 font-mono">CI/RIF: {{ $propietario->cedula_rif ?? 'V-00000000' }}</div>
                    </div>
                </div>
            </div>

            <!-- Detalles de la Transacción Bancaria Conciliada -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Datos de la Transacción Conciliada
                    </h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 block font-semibold uppercase">Forma Pago</span>
                        <span class="font-bold text-slate-900 uppercase">{{ str_replace('_', ' ', $payment->metodo_pago ?? 'N/A') }}</span>
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 block font-semibold uppercase">Banco Emisor</span>
                        <span class="font-bold text-slate-900">{{ $payment->banco ?? 'N/A' }}</span>
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 block font-semibold uppercase">N° Referencia</span>
                        <span class="font-bold text-indigo-600 font-mono">{{ $payment->referencia ?? 'S/R' }}</span>
                    </div>
                    <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 block font-semibold uppercase">Fecha Depósito</span>
                        <span class="font-bold text-slate-900">{{ !empty($payment->fecha_pago) ? \Carbon\Carbon::parse($payment->fecha_pago)->format('d/m/Y') : date('d/m/Y') }}</span>
                    </div>
                </div>

                <!-- Resumen de Montos Abonados -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex flex-col sm:flex-row justify-between items-center gap-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Monto Total Conciliado:</span>
                    <div class="text-right">
                        <div class="text-lg font-black text-emerald-600 font-mono">Bs. {{ number_format((float)$payment->monto, 2, ',', '.') }}</div>
                        <div class="text-xs text-slate-500 font-mono font-semibold">
                            ${{ number_format(((float)$payment->monto / (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50))), 2, ',', '.') }} USD (Tasa BCV: Bs. {{ number_format((float)($payment->tasa_cambio ?: 36.50), 2, ',', '.') }})
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recibos RI Saldados -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Recibos de Cobro (RI) Cubiertos por este Pago
                </h3>

                <div class="space-y-2">
                    @php
                        $coveredInvoices = $payment->invoices->isNotEmpty() ? $payment->invoices : collect([$payment->invoice])->filter();
                        $tasaPago = (float)($payment->tasa_cambio ?: ($condominio->tasa_cambio ?: 36.50));
                        $montoTotalPago = (float)$payment->monto;

                        $invoicesProcessed = [];
                        $montoRestanteParaDistribuir = $montoTotalPago;
                        $sumTotalAplicadoBs = 0;

                        foreach ($coveredInvoices as $invCov) {
                            $totalRiBs = (float)($invCov->monto_total_bs_efectivo > 0 ? $invCov->monto_total_bs_efectivo : $invCov->monto_total);
                            $totalRiUsd = (float)($invCov->monto_total_usd > 0 ? $invCov->monto_total_usd : ($totalRiBs / $tasaPago));

                            $pagadoHistorico = (float)$invCov->monto_pagado;
                            if ($payment->estado === 'aprobado') {
                                $saldoPendienteAntes = $totalRiBs;
                            } else {
                                $saldoPendienteAntes = max(0, $totalRiBs - $pagadoHistorico);
                            }
                            $deudaRequerida = $saldoPendienteAntes > 0 ? $saldoPendienteAntes : $totalRiBs;

                            if (isset($invCov->pivot->monto_aplicado) && (float)$invCov->pivot->monto_aplicado > 0 && (float)$invCov->pivot->monto_aplicado <= $deudaRequerida) {
                                $montoAplicadoBs = (float)$invCov->pivot->monto_aplicado;
                            } else {
                                $montoAplicadoBs = min($montoRestanteParaDistribuir, $deudaRequerida);
                            }

                            $sumTotalAplicadoBs += $montoAplicadoBs;
                            $montoRestanteParaDistribuir = max(0, $montoRestanteParaDistribuir - $montoAplicadoBs);

                            $esPagadoTotal = ($montoAplicadoBs >= ($deudaRequerida - 0.05)) || ($invCov->estado === 'pagado');

                            $invoicesProcessed[] = [
                                'inv' => $invCov,
                                'numero_factura' => $invCov->numero_factura ?? 'RI2026-X',
                                'periodo' => $invCov->periodo ?? '-',
                                'total_usd' => $totalRiUsd,
                                'total_bs' => $totalRiBs,
                                'monto_aplicado_bs' => $montoAplicadoBs,
                                'pagado_total' => $esPagadoTotal,
                            ];
                        }

                        $excedenteBs = max(0, $montoTotalPago - $sumTotalAplicadoBs);
                        $excedenteUsd = round($excedenteBs / $tasaPago, 2);
                    @endphp

                    @foreach($invoicesProcessed as $item)
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                            <div>
                                <div class="font-bold text-slate-900 font-mono">{{ $item['numero_factura'] }} ({{ $item['periodo'] }})</div>
                                <div class="text-[11px] text-slate-500">Total Adeudado: Bs. {{ number_format($item['total_bs'], 2, ',', '.') }} (${{ number_format($item['total_usd'], 2, ',', '.') }} USD)</div>
                            </div>
                            <div class="text-right">
                                <div class="font-extrabold text-emerald-600 font-mono">Bs. {{ number_format($item['monto_aplicado_bs'], 2, ',', '.') }}</div>
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded {{ $item['pagado_total'] ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }} uppercase">
                                    {{ $item['pagado_total'] ? 'Pagado Total' : 'Abono Parcial' }}
                                </span>
                            </div>
                        </div>
                    @endforeach

                    @if($payment->notaCreditoGenerada || $excedenteBs > 0.01)
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-300 text-xs">
                            <div>
                                <div class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span>✨ Excedente a Favor / Nota de Crédito</span>
                                    @if($payment->notaCreditoGenerada)
                                        <span class="font-mono bg-emerald-200 text-emerald-900 px-1.5 py-0.5 rounded font-bold">{{ $payment->notaCreditoGenerada->numero_nota_credito }}</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-emerald-700">Saldo a favor disponible para deducir de futuros recibos</div>
                            </div>
                            <div class="text-right">
                                <div class="font-extrabold text-emerald-700 font-mono">+Bs. {{ number_format($payment->notaCreditoGenerada?->monto_original ?? $excedenteBs, 2, ',', '.') }}</div>
                                <span class="text-[10px] text-emerald-600 font-mono font-semibold">+${{ number_format($payment->notaCreditoGenerada?->monto_original_usd ?? $excedenteUsd, 2, ',', '.') }} USD</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Vista Previa Integrada del Documento Oficial (PDF) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        Vista Previa del Comprobante PDF Original
                    </h3>
                    <a href="/api/v1/reportes/recibo/{{ $payment->id }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline">
                        Abrir PDF ↗
                    </a>
                </div>
                <div class="rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                    <iframe src="/api/v1/reportes/recibo/{{ $payment->id }}" class="w-full h-[450px] sm:h-[550px] border-0" title="Vista Previa Recibo RP"></iframe>
                </div>
            </div>

            <!-- Banner de Marketing e Identidad Tecnológica AZPRO -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl p-6 shadow-md border border-slate-800 space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-black text-white text-lg shadow-inner">
                            AZ
                        </div>
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-400 block">Plataforma Tecnológica Oficial</span>
                            <h3 class="text-sm font-extrabold text-white">Sistema de Gestión Inteligente de Condominios • AZPRO</h3>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full">
                        Emisión Segura & Certificada
                    </span>
                </div>

                <p class="text-xs text-slate-300 italic font-medium leading-relaxed">
                    "Transparencia total, innovación contable y control financiero en tiempo real para comunidades modernas."
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 text-[11px]">
                    <div class="bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 text-slate-300 flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-sm">✓</span>
                        <span>Conciliación Bancaria</span>
                    </div>
                    <div class="bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 text-slate-300 flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-sm">✓</span>
                        <span>Seguridad Criptográfica</span>
                    </div>
                    <div class="bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 text-slate-300 flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-sm">✓</span>
                        <span>Auditoría en Tiempo Real</span>
                    </div>
                </div>
            </div>

            <!-- Botón de Descarga -->
            <div class="text-center pt-2">
                <a href="/api/v1/reportes/recibo/{{ $payment->id }}" target="_blank" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-md hover:shadow-indigo-600/20 text-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Descargar Comprobante PDF (RP)
                </a>
            </div>
        @endif

    </div>

    <!-- Footer de Marketing -->
    <footer class="mt-8 text-center space-y-1">
        <p class="text-[11px] font-bold text-slate-700">
            DOCUMENTO EMITIDO MEDIANTE EL SISTEMA DE GESTIÓN INTELIGENTE DE CONDOMINIOS • AZPRO
        </p>
        <p class="text-[10px] text-slate-500 font-medium italic">
            "Transparencia total, innovación contable y control financiero en tiempo real para comunidades modernas."
        </p>
    </footer>

</body>
</html>
