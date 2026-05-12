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
        'modalidad_venta',
        'fecha_venta',
        'fecha_proximo_abono',
        'fecha_ultimo_abono',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_proximo_abono' => 'datetime',
        'fecha_ultimo_abono' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function abonos()
    {
        return $this->hasMany(VentaAbono::class, 'venta_id');
    }
}
