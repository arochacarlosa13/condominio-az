<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominioCuentaBancaria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'condominio_cuentas_bancarias';

    protected $fillable = [
        'condominio_id',
        'banco_nombre',
        'tipo_cuenta',
        'moneda',
        'numero_cuenta',
        'titular_nombre',
        'titular_identificacion',
        'telefono_pago_movil',
        'es_pago_movil',
        'instrucciones',
        'activo',
    ];

    protected $casts = [
        'es_pago_movil' => 'boolean',
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
}
