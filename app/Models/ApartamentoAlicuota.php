<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApartamentoAlicuota extends Model
{
    use HasFactory;

    protected $table = 'apartamento_alicuotas';

    protected $fillable = [
        'apartamento_id',
        'condominio_alicuota_id',
        'porcentaje',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:8',
    ];

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function condominioAlicuota()
    {
        return $this->belongsTo(CondominioAlicuota::class, 'condominio_alicuota_id');
    }
}
