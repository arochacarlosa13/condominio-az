<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apartamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'numero',
        'piso',
        'metros_cuadrados',
        'alicuota',
        'alicuota_2',
        'alicuota_3',
        'alicuota_4',
        'alicuota_5',
        'alicuota_6',
        'alicuota_7',
        'alicuota_8',
        'alicuota_9',
        'alicuota_10',
        'alicuota_11',
        'alicuota_12',
        'grupo_alicuota',
        'habitaciones',
        'banos',
        'ocupado',
        'descripcion',
    ];

    protected $casts = [
        'ocupado' => 'boolean',
        'metros_cuadrados' => 'decimal:2',
        'alicuota' => 'decimal:8',
    ];

    protected $appends = [
        'alicuotas_detalle',
        'alicuotas_activas',
    ];

    /**
     * Boot para aplicar scope global
     */
    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    /**
     * Relación con las alícuotas asignadas en tabla relacional
     */
    public function alicuotasAsignadas()
    {
        return $this->hasMany(ApartamentoAlicuota::class, 'apartamento_id')->with('condominioAlicuota');
    }

    /**
     * Relación directa con los tipos de alícuota del condominio
     */
    public function alicuotas()
    {
        return $this->belongsToMany(CondominioAlicuota::class, 'apartamento_alicuotas', 'apartamento_id', 'condominio_alicuota_id')
                    ->withPivot('porcentaje')
                    ->withTimestamps();
    }

    /**
     * Obtener el porcentaje de una alícuota específica buscando en la relación por ID o por número lógico
     */
    public function getAlicuota(int|string $aliNumeroOrId = 1): float
    {
        $target = (int)$aliNumeroOrId;

        // Cargar relación si no está en memoria
        if (!$this->relationLoaded('alicuotasAsignadas')) {
            $this->load('alicuotasAsignadas.condominioAlicuota');
        }

        if ($this->alicuotasAsignadas && $this->alicuotasAsignadas->isNotEmpty()) {
            // 1. Intentar coincidencia exacta por condominio_alicuota_id
            $asigById = $this->alicuotasAsignadas->firstWhere('condominio_alicuota_id', $target);
            if ($asigById && (float)$asigById->porcentaje > 0) {
                return (float)$asigById->porcentaje;
            }

            // 2. Intentar coincidencia por número lógico (Ali 1, Ali 2, Ali 3...)
            $asigByNum = $this->alicuotasAsignadas->first(function ($item) use ($target) {
                return $item->condominioAlicuota && ((int)$item->condominioAlicuota->numero === $target);
            });
            if ($asigByNum) {
                return (float)$asigByNum->porcentaje;
            }

            // Si se encontró por ID aunque el porcentaje sea 0
            if ($asigById) {
                return (float)$asigById->porcentaje;
            }
        }

        // 3. Fallback a columnas locales directas
        if ($target <= 1) {
            return (float)($this->alicuota > 0 ? $this->alicuota : 3.03460000);
        }

        $col = "alicuota_{$target}";
        if (isset($this->$col) && (float)$this->$col > 0) {
            return (float)$this->$col;
        }

        return 0.0;
    }

    /**
     * Detalle completo de alícuotas configuradas con nombre y porcentaje para el frontend y recibos
     */
    public function getAlicuotasDetalleAttribute(): array
    {
        $detalles = [];

        if ($this->relationLoaded('alicuotasAsignadas') && $this->alicuotasAsignadas->isNotEmpty()) {
            foreach ($this->alicuotasAsignadas as $asig) {
                $def = $asig->condominioAlicuota;
                if ($def && (float)$asig->porcentaje > 0) {
                    $detalles[] = [
                        'id' => $asig->id,
                        'condominio_alicuota_id' => $def->id,
                        'numero' => $def->numero,
                        'nombre' => $def->nombre,
                        'descripcion' => $def->descripcion,
                        'porcentaje' => (float)$asig->porcentaje,
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $ali1 = (float)($this->alicuota > 0 ? $this->alicuota : 3.03460000);
            $detalles[] = [
                'id' => 1,
                'condominio_alicuota_id' => 1,
                'numero' => 1,
                'nombre' => 'Gastos Generales',
                'descripcion' => 'Alícuota principal',
                'porcentaje' => $ali1,
            ];
            for ($i = 2; $i <= 12; $i++) {
                $col = "alicuota_{$i}";
                if (isset($this->$col) && (float)$this->$col > 0) {
                    $detalles[] = [
                        'id' => $i,
                        'condominio_alicuota_id' => $i,
                        'numero' => $i,
                        'nombre' => "Alícuota {$i}",
                        'descripcion' => '',
                        'porcentaje' => (float)$this->$col,
                    ];
                }
            }
        }

        return $detalles;
    }

    /**
     * Diccionario clave-valor de alícuotas activas para compatibilidad
     */
    public function getAlicuotasActivasAttribute(): array
    {
        $activas = [];
        foreach ($this->alicuotas_detalle as $item) {
            $activas[$item['numero']] = $item['porcentaje'];
        }
        return $activas;
    }

    /**
     * Relación con el condominio
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    /**
     * Relación con propietarios
     */
    public function propietarios()
    {
        return $this->hasMany(Propietario::class);
    }

    /**
     * Relación con facturas
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relación con reservas
     */
    public function reservas()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Relación con Notas de Crédito emitidas a favor del inmueble
     */
    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Obtener saldo total disponible por Notas de Crédito activas en Bolívares
     */
    public function getSaldoCreditoDisponibleBsAttribute(): float
    {
        return (float) $this->creditNotes()
            ->whereIn('estado', ['disponible', 'parcial'])
            ->where('monto_disponible', '>', 0)
            ->sum('monto_disponible');
    }

    /**
     * Obtener saldo total disponible por Notas de Crédito activas en USD
     */
    public function getSaldoCreditoDisponibleUsdAttribute(): float
    {
        return (float) $this->creditNotes()
            ->whereIn('estado', ['disponible', 'parcial'])
            ->where('monto_disponible_usd', '>', 0)
            ->sum('monto_disponible_usd');
    }

    /**
     * Obtener el propietario principal
     */
    public function propietarioPrincipal()
    {
        return $this->propietarios()->where('es_propietario_principal', true)->first();
    }
}