<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Condominio;
use App\Models\CondominioCuentaBancaria;
use Illuminate\Http\Request;

class CondominioCuentaController extends BaseController
{
    /**
     * Listado del catálogo de bancos nacionales.
     */
    public function bancos()
    {
        $bancos = \App\Models\Banco::where('activo', true)->orderBy('nombre', 'asc')->get();
        return $this->respuestaExitosa($bancos);
    }

    /**
     * Listar cuentas bancarias y canales de Pago Móvil de un condominio.
     */
    public function index(Request $request, $condominioId = null)
    {
        $cId = $condominioId ?: $this->obtenerCondominioActual();
        $query = CondominioCuentaBancaria::withoutGlobalScopes()
            ->where('condominio_id', $cId);

        if ($request->boolean('solo_activas')) {
            $query->where('activo', true);
        }

        $cuentas = $query
            ->orderBy('es_pago_movil', 'desc')
            ->orderBy('moneda', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->respuestaExitosa($cuentas);
    }

    /**
     * Crear una nueva cuenta bancaria o canal Pago Móvil.
     */
    public function store(Request $request, $condominioId = null)
    {
        $cId = $condominioId ?: $this->obtenerCondominioActual();

        $request->validate([
            'banco_nombre' => 'required|string|max:100',
            'tipo_cuenta' => 'required|string',
            'moneda' => 'required|string|in:VES,USD,EUR',
            'numero_cuenta' => 'nullable|string|max:50',
            'titular_nombre' => 'nullable|string|max:150',
            'titular_identificacion' => 'nullable|string|max:50',
            'telefono_pago_movil' => 'nullable|string|max:30',
            'es_pago_movil' => 'boolean',
            'instrucciones' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $cuenta = CondominioCuentaBancaria::create([
            'condominio_id' => $cId,
            'banco_nombre' => $request->banco_nombre,
            'tipo_cuenta' => $request->tipo_cuenta,
            'moneda' => $request->moneda,
            'numero_cuenta' => $request->numero_cuenta,
            'titular_nombre' => $request->titular_nombre,
            'titular_identificacion' => $request->titular_identificacion,
            'telefono_pago_movil' => $request->telefono_pago_movil,
            'es_pago_movil' => $request->boolean('es_pago_movil'),
            'instrucciones' => $request->instrucciones,
            'activo' => $request->boolean('activo', true),
        ]);

        return $this->respuestaExitosa($cuenta, 'Cuenta bancaria registrada exitosamente.', 201);
    }

    /**
     * Actualizar datos de una cuenta bancaria o Pago Móvil existente.
     */
    public function update(Request $request, CondominioCuentaBancaria $cuenta)
    {
        $request->validate([
            'banco_nombre' => 'required|string|max:100',
            'tipo_cuenta' => 'required|string',
            'moneda' => 'required|string|in:VES,USD,EUR',
            'numero_cuenta' => 'nullable|string|max:50',
            'titular_nombre' => 'nullable|string|max:150',
            'titular_identificacion' => 'nullable|string|max:50',
            'telefono_pago_movil' => 'nullable|string|max:30',
            'es_pago_movil' => 'boolean',
            'instrucciones' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $cuenta->update([
            'banco_nombre' => $request->banco_nombre,
            'tipo_cuenta' => $request->tipo_cuenta,
            'moneda' => $request->moneda,
            'numero_cuenta' => $request->numero_cuenta,
            'titular_nombre' => $request->titular_nombre,
            'titular_identificacion' => $request->titular_identificacion,
            'telefono_pago_movil' => $request->telefono_pago_movil,
            'es_pago_movil' => $request->boolean('es_pago_movil'),
            'instrucciones' => $request->instrucciones,
            'activo' => $request->boolean('activo', true),
        ]);

        return $this->respuestaExitosa($cuenta, 'Cuenta bancaria actualizada exitosamente.');
    }

    /**
     * Eliminar (soft delete) una cuenta bancaria.
     */
    public function destroy(CondominioCuentaBancaria $cuenta)
    {
        $cuenta->delete();
        return $this->respuestaExitosa(null, 'Cuenta bancaria eliminada.');
    }
}
