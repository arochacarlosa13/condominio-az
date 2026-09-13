<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'apartamento_id',
        'payment_id',
        'numero_nota_credito',
        'fecha_emision',
        'monto_original',
        'monto_original_usd',
        'monto_disponible',
        'monto_disponible_usd',
        'tasa_cambio',
        'estado',
        'motivo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'monto_original' => 'decimal:2',
        'monto_original_usd' => 'decimal:2',
        'monto_disponible' => 'decimal:2',
        'monto_disponible_usd' => 'decimal:2',
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

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Pago origen que generó el excedente de dinero
     */
    public function origenPago()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Pagos donde se ha utilizado total o parcialmente esta nota de crédito
     */
    public function pagosAplicados()
    {
        return $this->hasMany(Payment::class, 'credit_note_id');
    }

    /**
     * Descontar saldo utilizado de la nota de crédito
     */
    public function descontarSaldo(float $montoBs, float $montoUsd = 0)
    {
        $this->monto_disponible = max(0, (float)$this->monto_disponible - $montoBs);
        $this->monto_disponible_usd = max(0, (float)$this->monto_disponible_usd - $montoUsd);

        if ($this->monto_disponible <= 0.01) {
            $this->monto_disponible = 0;
            $this->monto_disponible_usd = 0;
            $this->estado = 'agotada';
        } else {
            $this->estado = 'parcial';
        }

        $this->save();
        return $this;
    }

    /**
     * Restaurar saldo en caso de rechazo o anulación del pago generado
     */
    public function restaurarSaldo(float $montoBs, float $montoUsd = 0)
    {
        $this->monto_disponible = min((float)$this->monto_original, (float)$this->monto_disponible + $montoBs);
        $this->monto_disponible_usd = min((float)$this->monto_original_usd, (float)$this->monto_disponible_usd + $montoUsd);

        if ($this->monto_disponible >= (float)$this->monto_original - 0.01) {
            $this->estado = 'disponible';
        } else {
            $this->estado = 'parcial';
        }

        $this->save();
        return $this;
    }

    /**
     * Generar correlativo anual único para la Nota de Crédito. Formato: NC2026-00001
     */
    public static function generarNumeroNotaCredito($condominioId, $fechaEmision = null): string
    {
        $fecha = $fechaEmision ? \Carbon\Carbon::parse($fechaEmision) : \Carbon\Carbon::now();
        $year = $fecha->year;

        $ultimo = static::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->whereYear('fecha_emision', $year)
            ->whereNotNull('numero_nota_credito')
            ->where('numero_nota_credito', 'like', "NC{$year}-%")
            ->orderBy('id', 'desc')
            ->value('numero_nota_credito');

        $secuencia = 1;
        if ($ultimo && preg_match('/^NC\d{4}-(\d+)$/', $ultimo, $matches)) {
            $secuencia = (int)$matches[1] + 1;
        }

        return sprintf("NC%d-%05d", $year, $secuencia);
    }
}
