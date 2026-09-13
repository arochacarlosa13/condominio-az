<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'costo_base',
        'costo_por_apartamento',
        'max_apartamentos',
        'precio_mensual',
        'badge',
        'destacado',
        'caracteristicas',
        'activo',
    ];

    protected $casts = [
        'costo_base' => 'decimal:2',
        'costo_por_apartamento' => 'decimal:2',
        'precio_mensual' => 'decimal:2',
        'max_apartamentos' => 'integer',
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'caracteristicas' => 'array',
    ];

    public function condominios()
    {
        return $this->hasMany(Condominio::class, 'plan_id');
    }
}
