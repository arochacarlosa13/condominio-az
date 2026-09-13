<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitante extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'apartamento_id',
        'nombre_completo',
        'cedula',
        'placa_vehiculo',
        'hora_entrada',
        'hora_salida',
        'motivo',
        'registrado_por',
    ];

    protected $casts = [
        'hora_entrada' => 'datetime',
        'hora_salida' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
