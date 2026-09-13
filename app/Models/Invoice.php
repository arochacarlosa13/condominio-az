<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'apartamento_id',
        'condominio_id',
        'numero_factura',
        'numero_recibo_general',
        'fecha_emision',
        'fecha_vencimiento',
        'monto_total',
        'monto_total_usd',
        'monto_alicuota_usd',
        'tasa_cambio',
        'monto_pagado',
        'estado',
        'estado_certificacion',
        'fecha_certificacion',
        'certificado_por_id',
        'veces_reabierto',
        'reabierto_por_id',
        'motivo_reapertura',
        'descripcion',
        'detalles_gastos',
        'fondos',
        'periodo',
        'tipo_recibo',
        'titulo_proyecto',
        'modalidad_calculo',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_certificacion' => 'datetime',
        'monto_total' => 'decimal:2',
        'monto_total_usd' => 'decimal:2',
        'monto_alicuota_usd' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'veces_reabierto' => 'integer',
        'detalles_gastos' => 'array',
        'fondos' => 'array',
    ];

    protected $appends = [
        'tasa_efectiva',
        'monto_total_bs_efectivo',
        'dias_transcurridos',
        'dias_restantes_congelacion',
        'tasa_congelada',
    ];

    /**
     * Días transcurridos desde la fecha de emisión hasta hoy
     */
    public function getDiasTranscurridosAttribute(): int
    {
        $fechaEmision = $this->fecha_emision 
            ? \Carbon\Carbon::parse($this->fecha_emision)->startOfDay() 
            : ($this->created_at ? $this->created_at->startOfDay() : \Carbon\Carbon::now()->startOfDay());
        
        $hoy = \Carbon\Carbon::now()->startOfDay();
        return (int) $fechaEmision->diffInDays($hoy, false);
    }

    /**
     * Determina si la tasa está congelada por estar pagada o por la regla de los primeros 5 días
     */
    public function getTasaCongeladaAttribute(): bool
    {
        if ($this->estado === 'pagado') {
            return true; // Pagado y congelado permanentemente
        }

        $condo = $this->condominio;
        if (!$condo || !$condo->mantener_tasa_emision_5_dias) {
            return false;
        }

        $diasLimite = $condo->dias_congelar_tasa ?: 5;
        return $this->dias_transcurridos < $diasLimite;
    }

    /**
     * Días restantes de congelación de tasa
     */
    public function getDiasRestantesCongelacionAttribute(): int
    {
        if ($this->estado === 'pagado') {
            return 0;
        }

        $condo = $this->condominio;
        if (!$condo || !$condo->mantener_tasa_emision_5_dias) {
            return 0;
        }

        $diasLimite = $condo->dias_congelar_tasa ?: 5;
        return max(0, $diasLimite - $this->dias_transcurridos);
    }

    /**
     * Tasa de cambio efectiva hoy para este recibo
     */
    public function getTasaEfectivaAttribute(): float
    {
        $condo = $this->condominio;
        $tasaEmision = (float)($this->tasa_cambio > 0 ? $this->tasa_cambio : ($condo?->tasa_cambio ?? 36.50));

        // 1. Si la factura está pagada y aprobada, la tasa queda congelada permanentemente al pago
        if ($this->estado === 'pagado') {
            $ultimoPago = $this->payments ? $this->payments->where('estado', 'aprobado')->sortByDesc('fecha_pago')->first() : null;
            return (float)($ultimoPago?->tasa_cambio ?: $tasaEmision);
        }

        $tasaActual = (float)($condo?->tasa_cambio ?? 36.50);

        // 2. Si está en borrador, responde 100% dinámicamente a la tasa del día del condominio
        if ($this->estado_certificacion === 'borrador') {
            return $tasaActual;
        }

        // 3. Si está certificado y aplica la regla de 5 días
        if ($condo && $condo->mantener_tasa_emision_5_dias) {
            $diasLimite = $condo->dias_congelar_tasa ?: 5;
            if ($this->dias_transcurridos < $diasLimite) {
                return $tasaEmision;
            }
            // Del 6to día en adelante, recalcula dinámicamente a la tasa del día
            return $tasaActual;
        }

        // 4. Si está certificado sin regla de 5 días, mantiene la tasa fijada en la emisión
        return $tasaEmision;
    }

    /**
     * Monto total en Bolívares efectivo recalculado con la tasa efectiva
     */
    public function getMontoTotalBsEfectivoAttribute(): float
    {
        $montoUsd = (float)($this->monto_total_usd > 0 
            ? $this->monto_total_usd 
            : ($this->monto_total / ($this->tasa_cambio ?: 36.50)));
        
        return round($montoUsd * $this->tasa_efectiva, 2);
    }

    /**
     * Boot para aplicar el scope global
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
     * Relación con el condominio
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    /**
     * Relación con los pagos recibidos
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Usuario que certificó el recibo
     */
    public function certificadoPor()
    {
        return $this->belongsTo(User::class, 'certificado_por_id');
    }

    /**
     * Super Admin que autorizó la reapertura
     */
    public function reabiertoPor()
    {
        return $this->belongsTo(User::class, 'reabierto_por_id');
    }

    /**
     * Determina si el recibo está certificado y bloqueado
     */
    public function estaCertificado(): bool
    {
        return $this->estado_certificacion === 'certificado';
    }

    /**
     * Determina si el recibo se puede editar por el administrador
     */
    public function puedeModificarse(): bool
    {
        return $this->estado_certificacion === 'borrador';
    }

    /**
     * Determina si ya no admite más reaperturas
     */
    public function limiteReaperturasAlcanzado(): bool
    {
        return $this->veces_reabierto >= 2;
    }

    /**
     * Determina si el recibo corresponde a una cuota extraordinaria
     */
    public function esExtraordinario(): bool
    {
        return ($this->tipo_recibo === 'extraordinario');
    }

    public function scopeOrdinarios($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('tipo_recibo')->orWhere('tipo_recibo', 'ordinario');
        });
    }

    public function scopeExtraordinarios($query)
    {
        return $query->where('tipo_recibo', 'extraordinario');
    }

    /**
     * Genera automáticamente el siguiente identificador correlativo para una Cuota Extraordinaria.
     * Formato: EXT-2026-01, EXT-2026-02...
     */
    public static function generarSiguientePeriodoExtraordinario($condominioId, $fecha = null): string
    {
        $fechaObj = $fecha ? \Carbon\Carbon::parse($fecha) : \Carbon\Carbon::now();
        $year = $fechaObj->year;

        $ultimosPeriodos = static::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->where('tipo_recibo', 'extraordinario')
            ->whereYear('fecha_emision', $year)
            ->pluck('periodo')
            ->unique();

        $secuenciaMax = 0;
        foreach ($ultimosPeriodos as $per) {
            if (preg_match('/^EXT-' . $year . '-(\d+)$/i', $per, $matches)) {
                $num = (int)$matches[1];
                if ($num > $secuenciaMax) {
                    $secuenciaMax = $num;
                }
            }
        }

        $siguiente = $secuenciaMax + 1;
        return sprintf("EXT-%d-%02d", $year, $siguiente);
    }

    /**
     * Genera el número de Recibo General único e inmodificable para un condominio y año.
     * Formato: RG2026-00001
     */
    public static function generarNumeroReciboGeneral($condominioId, $fechaEmision = null, $periodo = null): string
    {
        $fecha = $fechaEmision ? \Carbon\Carbon::parse($fechaEmision) : \Carbon\Carbon::now();
        $year = $fecha->year;

        // Si se indica el período, verificar si ya existe un RG previo en ese período y condominio
        if ($periodo) {
            $existente = static::withoutGlobalScopes()
                ->where('condominio_id', $condominioId)
                ->where('periodo', $periodo)
                ->whereNotNull('numero_recibo_general')
                ->value('numero_recibo_general');

            if ($existente) {
                return $existente;
            }
        }

        // Buscar el último Recibo General del año para este condominio
        $ultimoRecibo = static::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->whereYear('fecha_emision', $year)
            ->whereNotNull('numero_recibo_general')
            ->where('numero_recibo_general', 'like', "RG{$year}-%")
            ->orderBy('id', 'desc')
            ->value('numero_recibo_general');

        $secuencia = 1;
        if ($ultimoRecibo && preg_match('/^RG\d{4}-(\d+)$/', $ultimoRecibo, $matches)) {
            $secuencia = (int)$matches[1] + 1;
        }

        return sprintf("RG%d-%05d", $year, $secuencia);
    }

    /**
     * Genera el número de Recibo Individual único e inmodificable para un condominio y año,
     * vinculado al Recibo General correspondiente. La secuencia final es continua en todo el año.
     * Formato: RI2026-1-00001
     */
    public static function generarSiguienteNumeroFactura($condominioId, $numeroReciboGeneral, $fechaEmision = null): string
    {
        $fecha = $fechaEmision ? \Carbon\Carbon::parse($fechaEmision) : \Carbon\Carbon::now();
        $year = $fecha->year;

        // Extraer el número entero del Recibo General (ej: 1 de RG2026-00001)
        $numRgInt = 1;
        if ($numeroReciboGeneral && preg_match('/^RG\d{4}-(\d+)$/', $numeroReciboGeneral, $matches)) {
            $numRgInt = (int)$matches[1];
        }

        // Buscar el último correlativo individual emitido en el año para este condominio
        $ultimaFactura = static::withoutGlobalScopes()
            ->where('condominio_id', $condominioId)
            ->whereYear('fecha_emision', $year)
            ->whereNotNull('numero_factura')
            ->where('numero_factura', 'like', "RI{$year}-%")
            ->orderBy('id', 'desc')
            ->value('numero_factura');

        $secuencia = 1;
        if ($ultimaFactura && preg_match('/^RI\d{4}-\d+-(\d+)$/', $ultimaFactura, $matches)) {
            $secuencia = (int)$matches[1] + 1;
        }

        return sprintf("RI%d-%d-%05d", $year, $numRgInt, $secuencia);
    }

    /**
     * Recalcula el monto pagado acumulado y actualiza el estado de la factura (pendiente, parcial, pagado)
     * basándose en la suma de todos los pagos con estado 'aprobado' (directos y distribuidos en tabla pivote).
     */
    public function recalcularMontoPagadoYEstado()
    {
        $pagosDirectos = (float) Payment::withoutGlobalScopes()
            ->where('invoice_id', $this->id)
            ->where('estado', 'aprobado')
            ->whereDoesntHave('invoices')
            ->sum('monto');

        $pagosPivote = (float) \Illuminate\Support\Facades\DB::table('invoice_payment')
            ->join('payments', 'invoice_payment.payment_id', '=', 'payments.id')
            ->where('invoice_payment.invoice_id', $this->id)
            ->where('payments.estado', 'aprobado')
            ->whereNull('payments.deleted_at')
            ->sum('invoice_payment.monto_aplicado');

        $totalDeuda = (float)($this->monto_total_bs_efectivo > 0 ? $this->monto_total_bs_efectivo : $this->monto_total);
        $totalAprobado = round(min($totalDeuda, $pagosDirectos + $pagosPivote), 2);
        $this->monto_pagado = $totalAprobado;

        if ($totalAprobado >= ($totalDeuda - 0.05)) {
            $this->estado = 'pagado';
        } else if ($totalAprobado > 0.01) {
            $this->estado = 'parcial';
        } else {
            $this->estado = 'pendiente';
        }

        $this->save();
        return $this;
    }
}

