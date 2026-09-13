<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visitante;
use App\Models\Reservation;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\Propietario;
use App\Models\CreditNote;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends BaseController
{
    /**
     * Generación de Recibo de Pago en PDF con diseño profesional e identificador único de caja (RP2026-00001).
     */
    public function reciboPagoPdf(Payment $payment)
    {
        $payment->load(['invoice.apartamento.propietarios', 'invoices.apartamento.propietarios', 'condominio', 'cuentaBancaria', 'registradoPor', 'notaCreditoGenerada', 'creditNote']);
        $invoice = $payment->invoice ?? $payment->invoices->first();
        $condominio = $payment->condominio ?? $invoice?->condominio ?? Condominio::first();
        $apartamento = $invoice?->apartamento ?? Apartamento::first();
        $propietario = $apartamento?->propietarios?->first();

        $alicuota_percent = (float)($apartamento?->alicuota > 0 ? $apartamento->alicuota : 3.03460000);
        
        if ($payment->estado === 'aprobado') {
            $doc_numero = $payment->numero_recibo_pago ?: Payment::generarNumeroReciboPago($condominio?->id ?? 1, $payment->fecha_pago);
            $es_borrador = false;
        } else {
            $year = $payment->fecha_pago ? \Carbon\Carbon::parse($payment->fecha_pago)->format('Y') : date('Y');
            $doc_numero = $payment->numero_recibo_pago ?: "BORRADOR-RP{$year}-" . str_pad($payment->id, 5, '0', STR_PAD_LEFT);
            $es_borrador = true;
        }

        $fecha_emision = $payment->fecha_pago ? \Carbon\Carbon::parse($payment->fecha_pago)->format('Y-m-d') : date('Y-m-d');
        
        // Firma de seguridad SHA-256 inalterable para el código QR
        $token = substr(hash('sha256', 'RP_SECURE_' . $payment->id . '_' . $payment->created_at . '_' . $payment->referencia), 0, 24);
        $urlCertificacion = url("/certificacion/pago/{$payment->id}?token={$token}");

        // Generar código QR en formato Data-URI Base64 para incrustación directa e incondicional en DomPDF
        $qrCodeBase64 = null;
        try {
            $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($urlCertificacion);
            $qrContent = @file_get_contents($qrApiUrl);
            if ($qrContent) {
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        } catch (\Throwable $e) {}

        $html = view('pdf.comprobante_pago', compact(
            'invoice',
            'payment',
            'condominio',
            'apartamento',
            'propietario',
            'alicuota_percent',
            'doc_numero',
            'fecha_emision',
            'es_borrador',
            'urlCertificacion',
            'qrCodeBase64'
        ))->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])->loadHTML($html)->setPaper('letter', 'portrait');
        $nombreDoc = $es_borrador ? "COMPROBANTE-PENDIENTE-{$payment->id}.pdf" : "ComprobantePago-{$doc_numero}.pdf";
        return $pdf->stream($nombreDoc);
    }

    /**
     * Vista pública de Verificación y Certificación de Autenticidad de Recibo de Pago (Escanear QR)
     */
    public function certificacionPago(\Illuminate\Http\Request $request, Payment $payment)
    {
        $expectedToken = substr(hash('sha256', 'RP_SECURE_' . $payment->id . '_' . $payment->created_at . '_' . $payment->referencia), 0, 24);
        $providedToken = $request->query('token');

        // Validación estricta anti-manipulación de ID en la URL
        $esValido = (!empty($providedToken) && hash_equals($expectedToken, $providedToken));

        $payment->load(['invoice.apartamento.propietarios', 'invoices.apartamento.propietarios', 'condominio', 'cuentaBancaria', 'notaCreditoGenerada', 'creditNote']);
        $invoice = $payment->invoice ?? $payment->invoices->first();
        $condominio = $payment->condominio ?? $invoice?->condominio;
        $apartamento = $invoice?->apartamento;
        $propietario = $apartamento?->propietarios?->first();

        $doc_numero = $payment->numero_recibo_pago ?: "RP-PENDIENTE-{$payment->id}";

        return view('certificacion_pago', compact(
            'payment',
            'invoice',
            'condominio',
            'apartamento',
            'propietario',
            'doc_numero',
            'esValido'
        ));
    }

    /**
     * Generación de Recibo en PDF por Factura directa
     */
    public function reciboInvoicePdf($invoice)
    {
        if ($invoice instanceof Invoice) {
            $invoiceModel = $invoice;
        } else {
            $invoiceModel = Invoice::withoutGlobalScopes()->findOrFail($invoice);
        }

        $invoiceModel->load([
            'apartamento' => fn($q) => $q->withoutGlobalScopes()->with([
                'propietarios' => fn($pq) => $pq->withoutGlobalScopes(),
                'alicuotasAsignadas' => fn($aq) => $aq->withoutGlobalScopes()->with('condominioAlicuota')
            ]),
            'condominio' => fn($q) => $q->withoutGlobalScopes(),
        ]);

        $condominio = $invoiceModel->condominio ?? Condominio::withoutGlobalScopes()->find($invoiceModel->condominio_id);
        $apartamento = $invoiceModel->apartamento ?? Apartamento::withoutGlobalScopes()->find($invoiceModel->apartamento_id);
        $propietario = $apartamento?->propietarios?->first();

        $alicuota_percent = (float)($apartamento?->alicuota > 0 ? $apartamento->alicuota : 3.03460000);
        $fecha_emision = $invoiceModel->fecha_emision ? Carbon::parse($invoiceModel->fecha_emision)->format('Y-m-d') : date('Y-m-d');
        
        if (empty($invoiceModel->numero_factura)) {
            $numRg = $invoiceModel->numero_recibo_general ?: Invoice::generarNumeroReciboGeneral($condominio?->id ?? 1, $fecha_emision, $invoiceModel->periodo);
            $doc_numero = Invoice::generarSiguienteNumeroFactura($condominio?->id ?? 1, $numRg, $fecha_emision);
        } else {
            $doc_numero = $invoiceModel->numero_factura;
        }

        $periodo_nombre = $invoiceModel->periodo ?? 'Período';
        $es_borrador = ($invoiceModel->estado_certificacion === 'borrador');

        $gastos = $invoiceModel->detalles_gastos ?? [];
        if (empty($gastos) && $condominio) {
            $gastos = $this->obtenerGastosFallback($condominio, $apartamento);
        } elseif ($es_borrador && !empty($gastos) && $apartamento) {
            $gastos = array_map(function ($g) use ($apartamento, $alicuota_percent) {
                if (!empty($g['es_no_comun'])) {
                    return $g;
                }
                $montoItem = (float)($g['monto'] ?? 0);
                $aliIdOrGroup = $g['condominio_alicuota_id'] ?? ($g['ali'] ?? '1');
                $aliGroup = strval($g['ali'] ?? '1');
                $percent = $apartamento->getAlicuota($aliIdOrGroup);
                if ($percent <= 0 && $aliGroup === '1') {
                    $percent = $alicuota_percent;
                }
                $cuota = round($montoItem * ($percent / 100), 2);
                $g['alicu'] = $cuota;
                $g['alicuota_porcentaje'] = $percent;
                return $g;
            }, $gastos);
        }

        $fondo_mes_monto = $invoiceModel->fondos['monto_mes'] ?? null;
        $fondo_mes_alicuota = $invoiceModel->fondos['monto_alicuota'] ?? null;
        if ($fondo_mes_monto === null && $condominio) {
            $fondo_porcentaje = (float)($condominio->fondo_reserva_porcentaje ?? 10.00);
            $sumTotalGastos = !empty($gastos) ? array_sum(array_column($gastos, 'monto')) : 1378.40;
            $fondo_mes_monto = round($sumTotalGastos * ($fondo_porcentaje / 100), 2);
            $fondo_mes_alicuota = round($fondo_mes_monto * ($alicuota_percent / 100), 2);
        }

        $payment = null;

        $cuentasBancarias = \App\Models\CondominioCuentaBancaria::withoutGlobalScopes()
            ->where('condominio_id', $condominio?->id)
            ->where('activo', true)
            ->get();

        $html = view('pdf.recibo', [
            'invoice' => $invoiceModel,
            'payment' => $payment,
            'condominio' => $condominio,
            'apartamento' => $apartamento,
            'propietario' => $propietario,
            'alicuota_percent' => $alicuota_percent,
            'doc_numero' => $doc_numero,
            'periodo_nombre' => $periodo_nombre,
            'fecha_emision' => $fecha_emision,
            'gastos' => $gastos,
            'fondo_mes_monto' => $fondo_mes_monto,
            'fondo_mes_alicuota' => $fondo_mes_alicuota,
            'es_borrador' => $es_borrador,
            'cuentasBancarias' => $cuentasBancarias
        ])->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])->loadHTML($html)->setPaper('letter', 'portrait');
        $nombreDoc = $es_borrador ? "BORRADOR-Recibo-{$doc_numero}.pdf" : "Recibo-{$doc_numero}.pdf";
        return $pdf->stream($nombreDoc);
    }

    /**
     * Generar en un solo PDF paginado todos los recibos individuales de los propietarios de un período/cuota.
     */
    public function recibosLotePdf(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::withoutGlobalScopes()->find($condominioId) ?? Condominio::withoutGlobalScopes()->first();
        $periodo = $request->periodo;

        if (!$periodo) {
            abort(400, 'Debe especificar el período o identificador de cuota.');
        }

        $query = Invoice::withoutGlobalScopes()
            ->with([
                'apartamento' => fn($q) => $q->withoutGlobalScopes()->with([
                    'propietarios' => fn($pq) => $pq->withoutGlobalScopes(),
                    'alicuotasAsignadas' => fn($aq) => $aq->withoutGlobalScopes()->with('condominioAlicuota')
                ]),
                'condominio' => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->where('condominio_id', $condominio?->id)
            ->where('periodo', $periodo);

        if ($request->filled('tipo_recibo')) {
            $query->where('tipo_recibo', $request->tipo_recibo);
        }

        $invoices = $query->get()->sortBy(function ($inv) {
            return $inv->apartamento?->numero ?? $inv->id;
        });

        if ($invoices->isEmpty()) {
            abort(404, "No se encontraron recibos registrados para el período '{$periodo}'.");
        }

        $cuentasBancarias = \App\Models\CondominioCuentaBancaria::withoutGlobalScopes()
            ->where('condominio_id', $condominio?->id)
            ->where('activo', true)
            ->get();

        $items = [];
        $esExtraordinarioLote = false;
        $esBorradorLote = false;

        foreach ($invoices as $inv) {
            $apto = $inv->apartamento;
            $prop = $apto?->propietarios?->first();
            $alicuota_percent = (float)($apto?->alicuota > 0 ? $apto->alicuota : 3.03460000);
            $fecha_emision = $inv->fecha_emision ? Carbon::parse($inv->fecha_emision)->format('Y-m-d') : date('Y-m-d');
            $doc_numero = $inv->numero_factura ?: 'RI-' . $inv->id;
            $es_borrador = ($inv->estado_certificacion === 'borrador');
            if ($es_borrador) $esBorradorLote = true;
            if ($inv->tipo_recibo === 'extraordinario') $esExtraordinarioLote = true;

            $gastos = $inv->detalles_gastos ?? [];
            if (empty($gastos) && $condominio) {
                $gastos = $this->obtenerGastosFallback($condominio, $apto);
            }

            $items[] = [
                'invoice' => $inv,
                'condominio' => $condominio,
                'apartamento' => $apto,
                'propietario' => $prop,
                'alicuota_percent' => $alicuota_percent,
                'doc_numero' => $doc_numero,
                'periodo_nombre' => $inv->periodo,
                'fecha_emision' => $fecha_emision,
                'gastos' => $gastos,
                'es_borrador' => $es_borrador,
                'cuentasBancarias' => $cuentasBancarias,
            ];
        }

        $html = view('pdf.recibos_lote', [
            'items' => $items,
            'periodo_nombre' => $periodo,
        ])->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])->loadHTML($html)->setPaper('letter', 'portrait');
        $prefix = $esBorradorLote ? "BORRADOR-" : "";
        $tipoStr = $esExtraordinarioLote ? "CuotasExtraordinarias" : "RecibosMes";
        $nombreDoc = "{$prefix}Libro-{$tipoStr}-{$periodo}.pdf";

        return $pdf->stream($nombreDoc);
    }

    /**
     * Generación del Recibo General del Edificio en PDF (Presupuesto Consolidado de Gastos del Condominio)
     */
    public function reciboGeneralPdf(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();
        $periodo = $request->periodo;

        $invoices = Invoice::where('condominio_id', $condominio?->id)
                           ->when($periodo, function ($q) use ($periodo) {
                               $q->where('periodo', $periodo);
                           })
                           ->get();

        $firstInvoice = $invoices->first();
        $periodo_nombre = $periodo ?: ($firstInvoice?->periodo ?? 'Período Actual');
        $fecha_emision = $firstInvoice?->fecha_emision ? Carbon::parse($firstInvoice->fecha_emision)->format('Y-m-d') : date('Y-m-d');
        $es_borrador = $firstInvoice ? ($firstInvoice->estado_certificacion === 'borrador') : true;

        $doc_numero = $firstInvoice?->numero_recibo_general ?: Invoice::generarNumeroReciboGeneral($condominio?->id ?? 1, $fecha_emision, $periodo);

        $gastos = $firstInvoice?->detalles_gastos ?? [];
        $gastosComunes = array_values(array_filter($gastos, function ($g) {
            return empty($g['es_no_comun']);
        }));

        if (empty($gastosComunes) && $condominio) {
            $gastosComunes = $this->obtenerGastosFallback($condominio);
        }

        $es_extraordinario = ($firstInvoice?->tipo_recibo === 'extraordinario');
        $titulo_proyecto = $firstInvoice?->titulo_proyecto ?? '';

        $sumTotalGastos = !empty($gastosComunes) ? array_sum(array_column($gastosComunes, 'monto')) : 0;
        
        if ($es_extraordinario) {
            $fondo_porcentaje = 0.0;
            $fondo_mes_monto = 0.0;
            $total_gastos_usd = round($sumTotalGastos, 2);
        } else {
            $fondo_porcentaje = (float)($condominio?->fondo_reserva_porcentaje ?? 10.00);
            $fondo_mes_monto = round($sumTotalGastos * ($fondo_porcentaje / 100), 2);
            $total_gastos_usd = round($sumTotalGastos + $fondo_mes_monto, 2);
        }

        $tasa_bcv = (float)($firstInvoice?->tasa_efectiva ?? ($condominio?->tasa_cambio ?? 36.50));
        $total_gastos_bs = round($total_gastos_usd * $tasa_bcv, 2);
        $total_apartamentos = $invoices->isNotEmpty() ? $invoices->count() : Apartamento::where('condominio_id', $condominio?->id)->count();

        $html = view('pdf.recibo_general', [
            'condominio' => $condominio,
            'doc_numero' => $doc_numero,
            'periodo_nombre' => $periodo_nombre,
            'fecha_emision' => $fecha_emision,
            'es_borrador' => $es_borrador,
            'es_extraordinario' => $es_extraordinario,
            'titulo_proyecto' => $titulo_proyecto,
            'gastos' => $gastosComunes,
            'fondo_porcentaje' => $fondo_porcentaje,
            'fondo_mes_monto' => $fondo_mes_monto,
            'total_gastos_usd' => $total_gastos_usd,
            'tasa_bcv' => $tasa_bcv,
            'total_gastos_bs' => $total_gastos_bs,
            'total_apartamentos' => $total_apartamentos,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');
        $nombreDoc = $es_borrador ? "BORRADOR-ReciboGeneral-{$doc_numero}.pdf" : "ReciboGeneral-{$doc_numero}.pdf";
        return $pdf->download($nombreDoc);
    }

    /**
     * Generación de Recibo de Muestra por Período y Condominio
     */
    public function reciboPeriodoPdf(Request $request)
    {
        return $this->reciboGeneralPdf($request);
    }

    private function obtenerGastosFallback($condominio, $apartamento = null)
    {
        $ali1 = $apartamento ? $apartamento->getAlicuota(1) : 3.03460000;

        $dbConceptos = \App\Models\CondominioConcepto::where('condominio_id', $condominio->id)
            ->where('activo', true)
            ->orderBy('ali')
            ->orderBy('id')
            ->get();

        if ($dbConceptos->isNotEmpty()) {
            return $dbConceptos->map(function ($c) use ($apartamento, $ali1) {
                $montoEdificio = (float)$c->monto_base;
                $aliGroup = strval($c->ali ?? '1');
                $percent = $apartamento ? $apartamento->getAlicuota($aliGroup) : ($aliGroup === '1' ? $ali1 : 0.0);
                $factor = $percent / 100;

                return [
                    'ali' => $aliGroup,
                    'concepto' => $c->concepto,
                    'monto' => $montoEdificio,
                    'alicu' => round($montoEdificio * $factor, 2),
                    'alicuota_porcentaje' => $percent,
                ];
            })->toArray();
        }

        $dbExpenses = \App\Models\Expense::where('condominio_id', $condominio->id)->get();
        if ($dbExpenses->isNotEmpty()) {
            return $dbExpenses->map(function ($exp) use ($ali1) {
                $montoEdificio = (float)$exp->monto_usd > 0 ? (float)$exp->monto_usd : (float)($exp->monto_bs / 36.50);
                return [
                    'ali' => '1',
                    'concepto' => strtoupper($exp->descripcion),
                    'monto' => $montoEdificio,
                    'alicu' => round($montoEdificio * ($ali1 / 100), 2),
                    'alicuota_porcentaje' => $ali1,
                ];
            })->toArray();
        }

        return [];
    }

    /**
     * Reporte de Morosidad en PDF
     */
    public function reporteMorososPdf(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();

        $morosos = Invoice::with(['apartamento.propietarios'])
            ->when($condominio, function ($q) use ($condominio) {
                $q->where('condominio_id', $condominio->id);
            })
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->get();

        $html = view('pdf.morosos', compact('morosos', 'condominio'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');
        return $pdf->download("Reporte-Morosidad-{$condominio?->nombre}.pdf");
    }

    /**
     * Reporte de Visitantes en PDF
     */
    public function reportesVisitantesPdf(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();

        $query = Visitante::with('apartamento');
        if ($condominio) {
            $query->where('condominio_id', $condominio->id);
        }
        if ($request->has('fecha')) {
            $query->whereDate('hora_entrada', $request->fecha);
        }

        $visitantes = $query->orderBy('hora_entrada', 'desc')->get();

        $html = view('pdf.visitantes', compact('visitantes', 'condominio'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');
        return $pdf->download("Control-Visitantes-{$condominio?->nombre}.pdf");
    }

    /**
     * Generación de Nota de Crédito en PDF oficial
     */
    public function notaCreditoPdf(CreditNote $creditNote)
    {
        $creditNote->load([
            'condominio',
            'apartamento.propietarios',
            'origenPago.cuentaBancaria',
            'pagosAplicados.invoice',
            'pagosAplicados.invoices'
        ]);

        $condominio = $creditNote->condominio ?? Condominio::first();
        $apartamento = $creditNote->apartamento ?? Apartamento::first();
        $propietario = $apartamento?->propietarios?->first();
        $doc_numero = $creditNote->numero_nota_credito;
        $fecha_emision = $creditNote->fecha_emision ? \Carbon\Carbon::parse($creditNote->fecha_emision)->format('Y-m-d') : date('Y-m-d');
        $alicuota_percent = (float)($apartamento?->alicuota > 0 ? $apartamento->alicuota : 3.03460000);

        // Token de seguridad inalterable para el código QR
        $token = substr(hash('sha256', 'NC_SECURE_' . $creditNote->id . '_' . $creditNote->created_at . '_' . $creditNote->numero_nota_credito), 0, 24);
        $urlCertificacion = url("/certificacion/nota-credito/{$creditNote->id}?token={$token}");

        // Generar código QR en formato Data-URI Base64
        $qrCodeBase64 = null;
        try {
            $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($urlCertificacion);
            $qrContent = @file_get_contents($qrApiUrl);
            if ($qrContent) {
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        } catch (\Throwable $e) {}

        $html = view('pdf.nota_credito', compact(
            'creditNote',
            'condominio',
            'apartamento',
            'propietario',
            'doc_numero',
            'fecha_emision',
            'alicuota_percent',
            'urlCertificacion',
            'qrCodeBase64'
        ))->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])->loadHTML($html)->setPaper('letter', 'portrait');
        return $pdf->stream("NotaDeCredito-{$doc_numero}.pdf");
    }

    /**
     * Vista pública de Certificación de Autenticidad de Nota de Crédito (Escanear QR)
     */
    public function certificacionNotaCredito(Request $request, CreditNote $creditNote)
    {
        $expectedToken = substr(hash('sha256', 'NC_SECURE_' . $creditNote->id . '_' . $creditNote->created_at . '_' . $creditNote->numero_nota_credito), 0, 24);
        $providedToken = $request->query('token');

        $esValido = (!empty($providedToken) && hash_equals($expectedToken, $providedToken));

        $creditNote->load([
            'condominio',
            'apartamento.propietarios',
            'origenPago',
            'pagosAplicados'
        ]);

        $condominio = $creditNote->condominio;
        $apartamento = $creditNote->apartamento;
        $propietario = $apartamento?->propietarios?->first();
        $doc_numero = $creditNote->numero_nota_credito;

        return view('certificacion_nota_credito', compact(
            'creditNote',
            'condominio',
            'apartamento',
            'propietario',
            'doc_numero',
            'esValido'
        ));
    }

    /**
     * Reporte oficial de Propietarios y Residentes en PDF
     */
    public function reportePropietariosPdf(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();

        $query = $this->obtenerQueryPropietarios($request);
        $propietarios = $query->get();

        $totalActivos = $propietarios->where('activo', true)->count();
        $totalConApto = $propietarios->filter(fn($p) => !empty($p->apartamento_id))->count();
        $totalMetros = (float)$propietarios->sum(fn($p) => (float)($p->apartamento?->metros_cuadrados ?? 0));

        $html = view('pdf.propietarios', compact(
            'propietarios',
            'condominio',
            'totalActivos',
            'totalConApto',
            'totalMetros'
        ))->render();

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
                  ->loadHTML($html)
                  ->setPaper('letter', 'landscape');

        $nombreCondo = $condominio ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $condominio->nombre) : 'Condominio';
        $fecha = date('Ymd');
        return $pdf->stream("Reporte-Propietarios-{$nombreCondo}-{$fecha}.pdf");
    }

    /**
     * Reporte de Propietarios y Residentes en Excel (CSV estructurado con BOM UTF-8)
     */
    public function reportePropietariosExcel(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = Condominio::find($condominioId) ?? Condominio::first();

        $query = $this->obtenerQueryPropietarios($request);
        $propietarios = $query->get();

        $nombreCondo = $condominio ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $condominio->nombre) : 'Condominio';
        $filename = "Censo_Propietarios_{$nombreCondo}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($propietarios) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8 para apertura directa y limpia en Microsoft Excel sin problemas de acentos ni caracteres especiales
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados descriptivos
            fputcsv($handle, [
                'ID Usuario',
                'Nombre y Apellidos',
                'Cédula / Identificación',
                'Correo Electrónico',
                'Teléfono Principal',
                'Apartamento / Unidad',
                'Piso',
                'Torre / Edificio',
                'Metros Cuadrados (m²)',
                'Alícuota Principal (%)',
                'Rol Asignado',
                'Estado de Acceso',
                'Fecha de Registro'
            ], ';');

            foreach ($propietarios as $p) {
                $apto = $p->apartamento;
                $condo = $p->condominio ?? ($apto?->condominio);
                $torre = $condo?->torre_bloque ?: ($condo?->nombre ?: 'Principal');
                $estado = $p->activo ? 'Activo' : 'Inactivo';
                if (!empty($p->bloqueado_hasta) && Carbon::parse($p->bloqueado_hasta)->isFuture()) {
                    $estado = 'Bloqueado Temporalmente';
                }

                fputcsv($handle, [
                    $p->id,
                    $p->name,
                    $p->cedula ?: 'Sin cédula',
                    $p->email,
                    $p->telefono ?: 'No registrado',
                    $apto ? $apto->numero : 'Sin asignar',
                    $apto ? $apto->piso : '-',
                    $torre,
                    $apto ? number_format((float)$apto->metros_cuadrados, 2, ',', '') : '0,00',
                    $apto ? number_format((float)($apto->alicuota ?? 0), 4, ',', '') : '0,0000',
                    $p->rol ?: 'propietario',
                    $estado,
                    $p->created_at ? $p->created_at->format('d/m/Y H:i') : '-'
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function obtenerQueryPropietarios(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $authUser = auth()->user();

        $query = User::with([
            'apartamento.condominio',
            'condominio',
            'condominios',
            'propietario'
        ]);

        if ($authUser && !$authUser->esMaster()) {
            if ($condominioId) {
                $query->where(function ($q) use ($condominioId) {
                    $q->where('condominio_id', $condominioId)
                      ->orWhereHas('condominios', function ($cq) use ($condominioId) {
                          $cq->where('condominios.id', $condominioId);
                      });
                });
            }
            $query->where(function ($q) {
                $q->where('rol', 'propietario')
                  ->orWhere('rol', 'Propietario/Residente')
                  ->orWhere('rol', 'propietario/residente');
            });
        } elseif ($condominioId) {
            $query->where(function ($q) use ($condominioId) {
                $q->where('condominio_id', $condominioId)
                  ->orWhereHas('condominios', function ($cq) use ($condominioId) {
                      $cq->where('condominios.id', $condominioId);
                  });
            });
            if ($request->has('rol') && $request->rol) {
                $query->where('rol', $request->rol);
            }
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('cedula', 'ILIKE', "%{$search}%")
                  ->orWhere('telefono', 'ILIKE', "%{$search}%")
                  ->orWhereHas('apartamento', function ($aq) use ($search) {
                      $aq->where('numero', 'ILIKE', "%{$search}%")
                         ->orWhere('piso', 'ILIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('apartamento_id')) {
            if ($request->apartamento_id === 'sin_apartamento') {
                $query->whereNull('apartamento_id');
            } else {
                $query->where('apartamento_id', $request->apartamento_id);
            }
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('activo', filter_var($request->status, FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name', 'asc');
    }
}

