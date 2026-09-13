<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends BaseController
{
    /**
     * Listar facturas
     */
    public function index()
    {
        $invoices = Invoice::with(['apartamento.propietarios', 'pagos'])
                           ->latest()
                           ->paginate(15);

        return $this->respuestaExitosa($invoices, 'Facturas recuperadas exitosamente');
    }

    /**
     * Crear factura individual
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apartamento_id' => 'required|exists:apartamentos,id',
            'monto_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date|after:today',
            'periodo' => 'required|string|size:6',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $apartamento = Apartamento::find($request->apartamento_id);
        
        $invoice = Invoice::create([
            ...$request->all(),
            'condominio_id' => $apartamento->condominio_id,
            'numero_factura' => 'FAC-' . time() . '-' . $request->apartamento_id,
            'fecha_emision' => now(),
            'estado' => 'pendiente',
        ]);

        return $this->respuestaExitosa($invoice, 'Factura creada exitosamente', 201);
    }

    /**
     * Generar facturas masivas para todo el condominio
     */
    public function generarMasivo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condominio_id' => 'required|exists:condominios,id',
            'periodo' => 'required|string|size:6',
            'fecha_vencimiento' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $apartamentos = Apartamento::where('condominio_id', $request->condominio_id)
                                   ->where('ocupado', true)
                                   ->get();

        $contador = 0;
        foreach ($apartamentos as $apartamento) {
            $monto = $apartamento->condominio->cuota_mantenimiento_base;
            
            Invoice::create([
                'apartamento_id' => $apartamento->id,
                'condominio_id' => $request->condominio_id,
                'numero_factura' => 'FAC-' . $request->periodo . '-' . $apartamento->id,
                'fecha_emision' => now(),
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'monto_total' => $monto,
                'periodo' => $request->periodo,
                'estado' => 'pendiente',
            ]);
            
            $contador++;
        }

        return $this->respuestaExitosa([
            'facturas_generadas' => $contador,
        ], "Se generaron {$contador} facturas exitosamente");
    }

    /**
     * Mostrar factura específica
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['apartamento.propietarios', 'pagos.registradoPor']);

        return $this->respuestaExitosa($invoice, 'Factura recuperada exitosamente');
    }

    /**
     * Anular factura
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->monto_pagado > 0) {
            return $this->respuestaError('No se puede anular una factura con pagos registrados', 400);
        }

        $invoice->update(['estado' => 'anulado']);
        $invoice->delete();

        return $this->respuestaExitosa(null, 'Factura anulada exitosamente');
    }

    /**
     * Obtener facturas del propietario autenticado
     */
    public function misFacturas()
    {
        $user = auth()->user();
        $propietario = $user->propietario;

        if (!$propietario) {
            return $this->respuestaError('No tiene apartamentos asociados', 404);
        }

        $invoices = Invoice::where('apartamento_id', $propietario->apartamento_id)
                           ->with('pagos')
                           ->latest()
                           ->paginate(10);

        return $this->respuestaExitosa($invoices, 'Facturas recuperadas exitosamente');
    }
}