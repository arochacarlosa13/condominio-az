<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'cuenta_bancaria_id',
        'credit_note_id',
        'condominio_id',
        'numero_recibo_pago',
        'monto',
        'moneda_origen',
        'monto_divisa',
        'tasa_cambio',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'banco',
        'banco_origen',
        'telefono_origen',
        'cedula_origen',
        'estado',
        'comprobante_path',
        'observaciones',
        'motivo_rechazo',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
        'monto_divisa' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_payment')
                    ->withPivot('monto_aplicado')
                    ->withTimestamps();
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CondominioCuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function notaCreditoGenerada()
    {
        return $this->hasOne(CreditNote::class, 'payment_id');
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Genera el número de Recibo de Pago (Comprobante de Caja) único e inmodificable
     * para un condominio y año. Formato: RP2026-00001
     */
    public static function generarNumeroReciboPago($condominioId, $fechaPago = null): string
    {
        $fecha = $fechaPago ? \Carbon\Carbon::parse($fechaPago) : \Carbon\Carbon::now();
        $year = $fecha->year;

        // Buscar el último Recibo de Pago emitido en el año para este condominio
        $ultimoPago = static::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->whereYear('fecha_pago', $year)
            ->whereNotNull('numero_recibo_pago')
            ->where('numero_recibo_pago', 'like', "RP{$year}-%")
            ->orderBy('id', 'desc')
            ->value('numero_recibo_pago');

        $secuencia = 1;
        if ($ultimoPago && preg_match('/^RP\d{4}-(\d+)$/', $ultimoPago, $matches)) {
            $secuencia = (int)$matches[1] + 1;
        }

        return sprintf("RP%d-%05d", $year, $secuencia);
    }
}
