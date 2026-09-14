<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'descripcion',
        'monto_bs',
        'monto_usd',
        'tasa_cambio',
        'categoria',
        'fecha_gasto',
        'estado_pago',
        'proveedor',
        'referencia_pago',
        'cuenta_bancaria_id',
        'comprobante_path',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_gasto' => 'date',
        'monto_bs' => 'decimal:2',
        'monto_usd' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CondominioCuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
