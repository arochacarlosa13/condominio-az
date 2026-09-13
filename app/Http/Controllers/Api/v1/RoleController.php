<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        $query = Role::with(['permissions', 'menus']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('descripcion', 'ILIKE', "%{$search}%");
        }

        $roles = $query->paginate(20);
        return $this->respuestaExitosa($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:roles,nombre',
            'descripcion' => 'nullable|string',
            'status' => 'boolean',
            'permissions' => 'array',
            'menus' => 'array',
        ]);

        $role = Role::create([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'status' => $request->status ?? true,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        if ($request->has('menus')) {
            $role->menus()->sync($request->menus);
        }

        return $this->respuestaExitosa($role->load(['permissions', 'menus']), 'Rol creado exitosamente.', 201);
    }

    public function show(Role $role)
    {
        return $this->respuestaExitosa($role->load(['permissions', 'menus']));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nombre' => 'required|string|unique:roles,nombre,' . $role->id,
            'descripcion' => 'nullable|string',
            'status' => 'boolean',
            'permissions' => 'array',
            'menus' => 'array',
        ]);

        $role->update([
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'status' => $request->status ?? $role->status,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        if ($request->has('menus')) {
            $role->menus()->sync($request->menus);
        }

        return $this->respuestaExitosa($role->load(['permissions', 'menus']), 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $role)
    {
        $role->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Rol eliminado lógicamente.');
    }
}
