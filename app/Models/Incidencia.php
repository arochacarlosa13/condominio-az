<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incidencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'apartamento_id',
        'user_id',
        'titulo',
        'descripcion',
        'prioridad',
        'responsable_nombre',
        'fecha_solucion',
        'estado',
        'foto_adjunta',
    ];

    protected $casts = [
        'fecha_solucion' => 'date',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
