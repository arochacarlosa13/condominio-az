<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PagoController extends BaseController
{
    /**
     * Listar pagos
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $pagos = Payment::with(['invoice.apartamento', 'registradoPor'])
                        ->latest()
                        ->paginate(15);

        return $this->respuestaExitosa($pagos, 'Pagos recuperados exitosamente');
    }

    /**
     * Registrar un nuevo pago
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:efectivo,transferencia,pago_movil,deposito,otro',
            'referencia' => 'nullable|string|max:100',
            'banco' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);

            // Validar que la factura no esté anulada o ya pagada
            if ($invoice->estado === 'anulado') {
                return $this->respuestaError('No se puede pagar una factura anulada', 400);
            }

            if ($invoice->estado === 'pagado') {
                return $this->respuestaError('Esta factura ya está pagada completamente', 400);
            }

            // Validar que el monto no exceda lo pendiente
            $pendiente = $invoice->monto_total - $invoice->monto_pagado;
            if ($request->monto > $pendiente) {
                return $this->respuestaError("El monto excede el saldo pendiente: {$pendiente}", 400);
            }

            // Crear el pago
            $pago = Payment::create([
                ...$request->all(),
                'condominio_id' => $invoice->condominio_id,
                'registrado_por' => auth()->id(),
            ]);

            // Actualizar la factura
            $nuevoMontoPagado = $invoice->monto_pagado + $request->monto;
            $nuevoEstado = $nuevoMontoPagado >= $invoice->monto_total ? 'pagado' : 'parcial';

            $invoice->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
            ]);

            DB::commit();

            return $this->respuestaExitosa($pago->load('invoice'), 'Pago registrado exitosamente', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al registrar el pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mostrar un pago específico
     */
    public function show(Payment $payment): \Illuminate\Http\JsonResponse
    {
        $payment->load(['invoice.apartamento', 'registradoPor']);

        return $this->respuestaExitosa($payment, 'Pago recuperado exitosamente');
    }

    /**
     * Anular un pago (solo si no ha pasado mucho tiempo)
     */
    public function destroy(Payment $payment): \Illuminate\Http\JsonResponse
    {
        // Solo permitir anular pagos del día actual
        if (!$payment->created_at->isToday()) {
            return $this->respuestaError('Solo se pueden anular pagos del día actual', 400);
        }

        try {
            DB::beginTransaction();

            // Revertir el monto en la factura
            $invoice = $payment->invoice;
            $nuevoMontoPagado = $invoice->monto_pagado - $payment->monto;
            $nuevoEstado = $nuevoMontoPagado > 0 ? 'parcial' : 'pendiente';

            $invoice->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
            ]);

            $payment->delete();

            DB::commit();

            return $this->respuestaExitosa(null, 'Pago anulado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al anular el pago: ' . $e->getMessage(), 500);
        }
    }
}