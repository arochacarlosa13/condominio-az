<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends BaseController
{
    public function index()
    {
        $permissions = Permission::all()->groupBy('modulo');
        return $this->respuestaExitosa($permissions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:permissions,nombre',
            'descripcion' => 'nullable|string',
            'modulo' => 'required|string',
        ]);

        $permission = Permission::create($request->all());
        return $this->respuestaExitosa($permission, 'Permiso creado exitosamente.', 201);
    }
}
