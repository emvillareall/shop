<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DetallePedido
 *
 * @property $id
 * @property $cantidad_producto
 * @property $producto_id
 * @property $pedido_id
 * @property $created_at
 * @property $estado_dtpedidos
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class DetallePedido extends Model
{
    
    static $rules = [
		'cantidad_producto' => 'required',
		'producto_id' => 'required',
		'pedido_id' => 'required',
        'estado_dtpedidos'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cantidad_producto',
        'producto_id',
        'estado_dtpedidos',
        'talla_por_color',
        'id_color_producto',
        'pedido_id',
        'nombre_producto_snapshot',
        'color_snapshot',
        'talla_snapshot',
        'precio_unitario_snapshot',
        'subtotal_linea',
        'descuento_linea',
        'impuesto_linea',
    ];



}
