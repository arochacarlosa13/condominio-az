<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'role_id',
        'condominio_id',
        'apartamento_id',
        'telefono',
        'cedula',
        'activo',
        'intentos_fallidos',
        'bloqueado_hasta',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'bloqueado_hasta' => 'datetime',
        'intentos_fallidos' => 'integer',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Condominio principal o activo por defecto
     */
    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    /**
     * Todos los condominios y torres a los que tiene acceso
     */
    public function condominios()
    {
        return $this->belongsToMany(Condominio::class, 'condominio_user')
                    ->withPivot('es_principal')
                    ->withTimestamps();
    }

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function propietario()
    {
        return $this->hasOne(Propietario::class);
    }

    public function esMaster(): bool
    {
        return $this->rol === 'master' || $this->rol === 'Super Admin' || ($this->role && $this->role->slug === 'super-admin');
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin' || $this->rol === 'Admin de Condominio' || ($this->role && $this->role->slug === 'admin-condominio');
    }

    public function esSupervisor(): bool
    {
        return $this->rol === 'Supervisor' || ($this->role && $this->role->slug === 'supervisor');
    }

    public function esAnalista(): bool
    {
        return $this->rol === 'Analista del Sistema' || ($this->role && $this->role->slug === 'analista');
    }

    public function esPropietario(): bool
    {
        return $this->rol === 'propietario' || $this->rol === 'Propietario/Residente' || ($this->role && $this->role->slug === 'propietario');
    }

    public function hasPermission($permissionName): bool
    {
        if ($this->esMaster()) {
            return true;
        }

        if ($this->role) {
            return $this->role->hasPermission($permissionName);
        }

        return false;
    }

    public function puedeAccederCondominio($condominioId): bool
    {
        if ($this->esMaster()) {
            return true;
        }

        if ((int)$this->condominio_id === (int)$condominioId) {
            return true;
        }

        return $this->condominios()->where('condominios.id', $condominioId)->exists();
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }
}