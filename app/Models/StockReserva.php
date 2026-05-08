<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReserva extends Model
{
    protected $table = 'stock_reservas';

    protected $fillable = [
        'session_id',
        'producto_id',
        'color_id',
        'talla',
        'cantidad',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];
}

