<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;

class ExpenseController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $this->obtenerCondominioActual();

        $query = Expense::with(['registradoPor', 'condominio']);

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('categoria') && $request->categoria !== '') {
            $query->where('categoria', $request->categoria);
        }

        if ($request->has('estado_pago') && $request->estado_pago !== '') {
            $query->where('estado_pago', $request->estado_pago);
        }

        if ($request->has('mes') && !empty($request->mes)) {
            // Permite filtrar por año y mes (ej. '2026-09')
            $partes = explode('-', $request->mes);
            if (count($partes) === 2) {
                $query->whereYear('fecha_gasto', $partes[0])
                      ->whereMonth('fecha_gasto', $partes[1]);
            }
        }

        if ($request->boolean('all')) {
            $expenses = $query->orderBy('fecha_gasto', 'desc')->get();
            return $this->respuestaExitosa($expenses);
        }

        $perPage = $request->get('per_page', 20);
        $expenses = $query->orderBy('fecha_gasto', 'desc')->paginate($perPage);
        return $this->respuestaExitosa($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto_bs' => 'nullable|numeric|min:0',
            'monto_usd' => 'nullable|numeric|min:0',
            'tasa_cambio' => 'required|numeric|min:0.01',
            'categoria' => 'required|string',
            'fecha_gasto' => 'required|date',
            'estado_pago' => 'required|in:pendiente,pagado',
            'proveedor' => 'nullable|string',
            'referencia_pago' => 'nullable|string',
            'ali' => 'nullable|string',
        ]);

        $condominioId = $this->obtenerCondominioActual();
        $tasa = (float)$request->tasa_cambio;

        if ($request->has('monto_usd') && is_numeric($request->monto_usd) && (float)$request->monto_usd > 0) {
            $montoUsd = (float)$request->monto_usd;
            $montoBs = $request->monto_bs ? (float)$request->monto_bs : round($montoUsd * $tasa, 2);
        } else {
            $montoBs = (float)($request->monto_bs ?? 0);
            $montoUsd = $tasa > 0 ? round($montoBs / $tasa, 2) : 0;
        }

        $expense = Expense::create([
            'condominio_id' => $condominioId,
            'descripcion' => $request->descripcion,
            'monto_bs' => $montoBs,
            'monto_usd' => $montoUsd,
            'tasa_cambio' => $tasa,
            'categoria' => $request->categoria,
            'fecha_gasto' => $request->fecha_gasto,
            'estado_pago' => $request->estado_pago,
            'proveedor' => $request->proveedor,
            'referencia_pago' => $request->referencia_pago,
            'registrado_por' => auth()->id(),
        ]);

        return $this->respuestaExitosa($expense, 'Gasto registrado exitosamente.', 201);
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'monto_bs' => 'required|numeric|min:0',
            'tasa_cambio' => 'required|numeric|min:0.01',
            'categoria' => 'required|string',
            'fecha_gasto' => 'required|date',
            'estado_pago' => 'required|in:pendiente,pagado',
            'proveedor' => 'nullable|string',
            'referencia_pago' => 'nullable|string',
        ]);

        $montoUsd = $request->monto_bs / $request->tasa_cambio;
        $data = $request->all();
        $data['monto_usd'] = $montoUsd;

        $expense->update($data);
        return $this->respuestaExitosa($expense, 'Gasto actualizado exitosamente.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Gasto eliminado lógicamente.');
    }
}
