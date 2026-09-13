<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'icono',
        'ruta',
        'orden',
        'padre_id',
        'permission_id',
    ];

    public function padre()
    {
        return $this->belongsTo(Menu::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(Menu::class, 'padre_id')->orderBy('orden');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }
}
