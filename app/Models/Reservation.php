<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'common_area_id',
        'user_id',
        'apartamento_id',
        'condominio_id',
        'fecha_reserva',
        'hora_inicio',
        'hora_fin',
        'estado',
        'motivo',
        'numero_personas',
        'costo_total',
        'pago_realizado',
        'notas',
    ];

    protected $casts = [
        'fecha_reserva' => 'date',
        'pago_realizado' => 'boolean',
        'costo_total' => 'decimal:2',
        'numero_personas' => 'integer',
    ];

    /**
     * Boot para aplicar el scope global
     */
    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    /**
     * Relación con el área común
     */
    public function commonArea()
    {
        return $this->belongsTo(CommonArea::class);
    }

    /**
     * Relación con el usuario (quien reservó)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el apartamento asociado
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Relación con el condominio
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }
}
