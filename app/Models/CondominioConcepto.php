<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominioConcepto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'condominio_conceptos';

    protected $fillable = [
        'condominio_id',
        'ali',
        'concepto',
        'monto_base',
        'tipo',
        'categoria',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];

    protected $casts = [
        'monto_base' => 'decimal:2',
        'activo' => 'boolean',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }
}
