<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondominioAlicuota extends Model
{
    use HasFactory;

    protected $table = 'condominio_alicuotas';

    protected $fillable = [
        'condominio_id',
        'numero',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'numero' => 'integer',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(ApartamentoAlicuota::class, 'condominio_alicuota_id');
    }
}
