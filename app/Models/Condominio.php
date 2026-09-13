<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condominio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'nombre',
        'tipo_entidad',
        'torre_bloque',
        'direccion',
        'rif',
        'telefono',
        'email',
        'numero_apartamentos',
        'cuota_mantenimiento_base',
        'porcentaje_mora',
        'dias_gracia',
        'moneda_base',
        'tasa_cambio',
        'mantener_tasa_emision_5_dias',
        'dias_congelar_tasa',
        'cuenta_bancaria_bs',
        'cuenta_bancaria_usd',
        'banco_nombre',
        'pago_movil_banco',
        'pago_movil_cedula',
        'pago_movil_telefono',
        'fondo_reserva_porcentaje',
        'fondo_reserva_acumulado',
        'notas_recibo',
        'plan_id',
        'plan_suscripcion',
        'fecha_vencimiento_suscripcion',
        'estado_suscripcion',
        'activo',
    ];

    protected $casts = [
        'cuota_mantenimiento_base' => 'decimal:2',
        'porcentaje_mora' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
        'mantener_tasa_emision_5_dias' => 'boolean',
        'dias_congelar_tasa' => 'integer',
        'fecha_vencimiento_suscripcion' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Conjunto Residencial Padre (si este registro es una torre/edificio hijo)
     */
    public function parent()
    {
        return $this->belongsTo(Condominio::class, 'parent_id');
    }

    /**
     * Torres o Edificios Hijos (si este registro es un conjunto matriz)
     */
    public function torres()
    {
        return $this->hasMany(Condominio::class, 'parent_id');
    }

    /**
     * Administradores asignados a este condominio o torre
     */
    public function administradores()
    {
        return $this->belongsToMany(User::class, 'condominio_user')
                    ->withPivot('es_principal')
                    ->withTimestamps();
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function apartamentos()
    {
        return $this->hasMany(Apartamento::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function commonAreas()
    {
        return $this->hasMany(CommonArea::class);
    }

    public function cuentasBancarias()
    {
        return $this->hasMany(CondominioCuentaBancaria::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function conceptos()
    {
        return $this->hasMany(CondominioConcepto::class);
    }

    public function alicuotas()
    {
        return $this->hasMany(CondominioAlicuota::class)->orderBy('numero');
    }

    public function visitantes()
    {
        return $this->hasMany(Visitante::class);
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencias::class);
    }

    public function comunicados()
    {
        return $this->hasMany(Comunicado::class);
    }

    public function saasInvoices()
    {
        return $this->hasMany(SaasInvoice::class);
    }

    public function esConjunto(): bool
    {
        return $this->tipo_entidad === 'conjunto_residencial';
    }

    public function esTorre(): bool
    {
        return $this->tipo_entidad === 'torre_edificio' || $this->parent_id !== null;
    }

    public function esIndependiente(): bool
    {
        return $this->tipo_entidad === 'edificio_independiente';
    }
}