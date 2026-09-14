<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poll extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado', // 'borrador', 'activa', 'finalizada'
        'permitir_multiple',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'permitir_multiple' => 'boolean',
    ];

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function options()
    {
        return $this->hasMany(PollOption::class)->orderBy('orden');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
