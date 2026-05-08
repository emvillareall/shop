<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'accion',
        'entidad',
        'entidad_id',
        'valores_antes',
        'valores_despues',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'valores_antes' => 'array',
        'valores_despues' => 'array',
    ];
}

