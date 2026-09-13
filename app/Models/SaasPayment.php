<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaasPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'saas_invoice_id',
        'condominio_id',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'banco',
        'monto_usd',
        'monto_bs',
        'tasa_cambio',
        'estado',
        'comprobante_path',
        'notas',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_usd' => 'decimal:2',
        'monto_bs' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
    ];

    public function saasInvoice()
    {
        return $this->belongsTo(SaasInvoice::class);
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
