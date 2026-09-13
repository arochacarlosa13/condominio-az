<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaasInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'plan_id',
        'numero_factura',
        'periodo',
        'fecha_emision',
        'fecha_vencimiento',
        'monto_total_usd',
        'monto_total_bs',
        'tasa_cambio',
        'monto_pagado_usd',
        'monto_pagado_bs',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total_usd' => 'decimal:2',
        'monto_total_bs' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
        'monto_pagado_usd' => 'decimal:2',
        'monto_pagado_bs' => 'decimal:2',
    ];

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function saasPayments()
    {
        return $this->hasMany(SaasPayment::class);
    }
}
