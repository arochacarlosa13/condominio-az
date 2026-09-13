<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propietario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'apartamento_id',
        'user_id',
        'condominio_id',
        'nombre_completo',
        'cedula',
        'telefono',
        'email',
        'es_propietario_principal',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'es_propietario_principal' => 'boolean',
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Boot para aplicar scope global
     */
    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    /**
     * Relación con el apartamento
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el condominio
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    /**
     * Verificar si el propietario está activo actualmente
     */
    public function estaActivo(): bool
    {
        if (!$this->activo) {
            return false;
        }
        
        if ($this->fecha_fin && now()->gt($this->fecha_fin)) {
            return false;
        }
        
        return now()->gte($this->fecha_inicio);
    }
}