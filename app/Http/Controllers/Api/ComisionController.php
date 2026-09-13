<?php

namespace App\Http\Controllers\Api;

use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ComisionController extends BaseController
{
    /**
     * Listar comisiones
     */
    public function index()
    {
        $comisiones = Commission::with(['payment.invoice', 'condominio'])
                                ->latest()
                                ->paginate(15);

        return $this->respuestaExitosa($comisiones, 'Comisiones recuperadas exitosamente');
    }

    /**
     * Registrar comisión
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condominio_id' => 'required|exists:condominios,id',
            'payment_id' => 'nullable|exists:payments,id',
            'porcentaje' => 'required|numeric|min:0|max:100',
            'monto' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $comision = Commission::create($request->all());

        return $this->respuestaExitosa($comision, 'Comisión registrada exitosamente', 201);
    }
}