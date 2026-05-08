<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraItem extends Model
{
    protected $table = 'compra_items';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'colores_id',
        'talla',
        'cantidad',
        'stock_ingresado',
        'costo_unitario_pesos',
        'costo_unitario_dolares',
        'subtotal_pesos',
        'subtotal_dolares',
        'estado',
        'observacion',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}

