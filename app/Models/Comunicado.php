<?php

namespace App\Models;

use App\Scopes\CondominioScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comunicado extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'condominio_id',
        'titulo',
        'contenido',
        'fecha_publicacion',
        'fecha_expiracion',
        'archivo_path',
        'enviar_email',
        'enviar_whatsapp',
    ];

    protected $casts = [
        'fecha_publicacion' => 'date',
        'fecha_expiracion' => 'date',
        'enviar_email' => 'boolean',
        'enviar_whatsapp' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CondominioScope());
    }

    public function condominio()
    {
        return $this->belongsTo(Condominio::class);
    }
}
