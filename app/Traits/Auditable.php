<?php

namespace App\Traits;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot del trait para registrar auditoría automáticamente
     */
    protected static function bootAuditable()
    {
        // Crear registro de auditoría al crear
        static::created(function ($model) {
            $model->registrarAuditoria('create');
        });

        // Crear registro de auditoría al actualizar
        static::updated(function ($model) {
            $model->registrarAuditoria('update');
        });

        // Crear registro de auditoría al eliminar (soft delete)
        static::deleted(function ($model) {
            $model->registrarAuditoria('delete');
        });
    }

    /**
     * Registrar una acción en la auditoría
     */
    public function registrarAuditoria(string $accion)
    {
        $user = Auth::user();
        
        AuditTrail::create([
            'user_id' => $user ? $user->id : null,
            'condominio_id' => $this->condominio_id ?? null,
            'accion' => $accion,
            'modelo' => get_class($this),
            'modelo_id' => $this->id,
            'datos_anteriores' => $this->getOriginal(),
            'datos_nuevos' => $this->getAttributes(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'descripcion' => $this->generarDescripcion($accion),
        ]);
    }

    /**
     * Generar descripción legible de la acción
     */
    protected function generarDescripcion(string $accion): string
    {
        $user = Auth::user();
        $userName = $user ? $user->name : 'Sistema';
        $modelo = class_basename($this);
        $identificador = $this->getIdentificador();
        
        return "{$userName} ha " . match($accion) {
            'create' => "creado un nuevo {$modelo}: {$identificador}",
            'update' => "actualizado {$modelo}: {$identificador}",
            'delete' => "eliminado {$modelo}: {$identificador}",
            default => "realizado acción '{$accion}' en {$modelo}: {$identificador}"
        };
    }

    /**
     * Obtener identificador del modelo (sobrescribir en cada modelo)
     */
    protected function getIdentificador(): string
    {
        return (string) ($this->id ?? 'Nuevo');
    }
}