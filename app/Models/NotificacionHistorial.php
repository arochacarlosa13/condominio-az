<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificacionHistorial extends Model
{
    use HasFactory;

    protected $table = 'notificacion_historial';

    protected $fillable = [
        'condominio_id',
        'user_id',
        'tipo',
        'plantilla',
        'destinatario',
        'mensaje',
        'estado',
        'error_mensaje',
    ];

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
