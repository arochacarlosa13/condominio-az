<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends BaseController
{
    public function index()
    {
        $menus = Menu::with(['hijos', 'permission', 'roles'])
            ->whereNull('padre_id')
            ->orderBy('orden')
            ->get();

        return $this->respuestaExitosa($menus);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'icono' => 'nullable|string',
            'ruta' => 'nullable|string',
            'orden' => 'integer',
            'padre_id' => 'nullable|exists:menus,id',
            'permission_id' => 'nullable|exists:permissions,id',
            'roles' => 'array',
        ]);

        $menu = Menu::create($request->only([
            'nombre', 'icono', 'ruta', 'orden', 'padre_id', 'permission_id'
        ]));

        if ($request->has('roles')) {
            $menu->roles()->sync($request->roles);
        }

        return $this->respuestaExitosa($menu->load(['hijos', 'roles']), 'Menú creado exitosamente.', 201);
    }

    public function show(Menu $menu)
    {
        return $this->respuestaExitosa($menu->load(['hijos', 'permission', 'roles']));
    }

    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'icono' => 'nullable|string',
            'ruta' => 'nullable|string',
            'orden' => 'integer',
            'padre_id' => 'nullable|exists:menus,id',
            'permission_id' => 'nullable|exists:permissions,id',
            'roles' => 'array',
        ]);

        $menu->update($request->only([
            'nombre', 'icono', 'ruta', 'orden', 'padre_id', 'permission_id'
        ]));

        if ($request->has('roles')) {
            $menu->roles()->sync($request->roles);
        }

        return $this->respuestaExitosa($menu->load(['hijos', 'roles']), 'Menú actualizado exitosamente.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Menú eliminado lógicamente.');
    }
}
