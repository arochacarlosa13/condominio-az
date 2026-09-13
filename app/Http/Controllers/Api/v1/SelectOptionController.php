<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\SelectOption;
use Illuminate\Http\Request;

class SelectOptionController extends BaseController
{
    public function index(Request $request)
    {
        $query = SelectOption::query();

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $options = $query->orderBy('tipo')->orderBy('etiqueta')->get();
        return $this->respuestaExitosa($options);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'valor' => 'required|string',
            'etiqueta' => 'required|string',
            'activo' => 'boolean',
        ]);

        $option = SelectOption::create($request->all());
        return $this->respuestaExitosa($option, 'Opción agregada exitosamente.', 201);
    }

    public function update(Request $request, SelectOption $selectOption)
    {
        $request->validate([
            'tipo' => 'required|string',
            'valor' => 'required|string',
            'etiqueta' => 'required|string',
            'activo' => 'boolean',
        ]);

        $selectOption->update($request->all());
        return $this->respuestaExitosa($selectOption, 'Opción actualizada exitosamente.');
    }

    public function destroy(SelectOption $selectOption)
    {
        $selectOption->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Opción eliminada lógicamente.');
    }
}
