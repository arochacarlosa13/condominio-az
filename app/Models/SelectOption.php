<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelectOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo',
        'valor',
        'etiqueta',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
