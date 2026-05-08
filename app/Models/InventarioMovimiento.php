<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'producto_id',
        'colores_productos_id',
        'pedido_id',
        'compra_id',
        'user_id',
        'tipo_movimiento',
        'cantidad',
        'stock_antes',
        'stock_despues',
        'motivo',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

