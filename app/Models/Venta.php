<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'pedido_id',
        'clientes_id',
        'tienda_id',
        'subtotal',
        'descuento',
        'total',
        'estado_venta',
        'fecha_venta',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}

