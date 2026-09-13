<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommonArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'common_areas';

    protected $fillable = [
        'condominio_id',
        'nombre',
        'descripcion',
        'ubicacion',
        'capacidad_maxima',
        'requiere_reserva',
        'costo_reserva',
        'horarios_disponibles',
        'reglas_uso',
        'activo',
    ];

    protected $casts = [
        'requiere_reserva' => 'boolean',
        'activo' => 'boolean',
        'costo_reserva' => 'decimal:2',
        'horarios_disponibles' => 'array',
        'reglas_uso' => 'array',
        'capacidad_maxima' => 'integer',
    ];

    /**
     * Boot para aplicar el scope global
     */
    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    /**
     * Relación con el condominio
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    /**
     * Relación con las reservas asociadas a esta área
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
