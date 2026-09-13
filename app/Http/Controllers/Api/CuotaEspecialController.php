<?php

namespace App\Http\Controllers\Api;

use App\Models\SpecialFee;
use App\Models\Apartamento;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CuotaEspecialController extends BaseController
{
    /**
     * Listar cuotas especiales
     */
    public function index()
    {
        $cuotas = SpecialFee::with('condominio')
                            ->latest()
                            ->paginate(15);

        return $this->respuestaExitosa($cuotas, 'Cuotas especiales recuperadas exitosamente');
    }

    /**
     * Crear cuota especial
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condominio_id' => 'required|exists:condominios,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'monto_total' => 'required|numeric|min:0',
            'monto_por_apartamento' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'periodo' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $cuota = SpecialFee::create($request->all());

        return $this->respuestaExitosa($cuota, 'Cuota especial creada exitosamente', 201);
    }

    /**
     * Mostrar cuota especial
     */
    public function show(SpecialFee $specialFee)
    {
        return $this->respuestaExitosa($specialFee->load('condominio'), 'Cuota recuperada exitosamente');
    }

    /**
     * Actualizar cuota especial
     */
    public function update(Request $request, SpecialFee $specialFee)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|required|string|max:1000',
            'monto_total' => 'sometimes|required|numeric|min:0',
            'monto_por_apartamento' => 'sometimes|required|numeric|min:0',
            'fecha_fin' => 'sometimes|required|date',
            'estado' => 'sometimes|in:activa,finalizada,cancelada',
        ]);

        if ($validator->fails()) {
            return $this->respuestaError('Error de validación', 422, $validator->errors());
        }

        $specialFee->update($request->all());

        return $this->respuestaExitosa($specialFee, 'Cuota actualizada exitosamente');
    }

    /**
     * Aplicar cuota a todos los apartamentos
     */
    public function aplicarATodos(SpecialFee $cuota)
    {
        try {
            DB::beginTransaction();

            $apartamentos = Apartamento::where('condominio_id', $cuota->condominio_id)
                                       ->where('ocupado', true)
                                       ->get();

            $contador = 0;
            foreach ($apartamentos as $apartamento) {
                Invoice::create([
                    'apartamento_id' => $apartamento->id,
                    'condominio_id' => $cuota->condominio_id,
                    'numero_factura' => 'CE-' . $cuota->id . '-' . $apartamento->id,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => $cuota->fecha_fin,
                    'monto_total' => $cuota->monto_por_apartamento,
                    'periodo' => $cuota->periodo,
                    'descripcion' => "Cuota especial: {$cuota->nombre}",
                    'estado' => 'pendiente',
                ]);
                $contador++;
            }

            DB::commit();

            return $this->respuestaExitosa([
                'facturas_generadas' => $contador,
            ], "Cuota aplicada a {$contador} apartamentos");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al aplicar cuota: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar cuota especial
     */
    public function destroy(SpecialFee $specialFee)
    {
        $specialFee->update(['estado' => 'cancelada']);
        $specialFee->delete();

        return $this->respuestaExitosa(null, 'Cuota cancelada exitosamente');
    }
}