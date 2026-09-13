<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    // Desactivar updated_at porque los logs son solo inserciones históricas de solo lectura
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'detalle',
        'ip_address',
    ];

    /**
     * Relación con el usuario que ejecutó la acción
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
