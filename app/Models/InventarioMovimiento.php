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
        'compra_item_id',
        'compra_recepcion_id',
        'compra_recepcion_item_id',
        'proveedor_reclamo_reposicion_id',
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

    public function compraItem()
    {
        return $this->belongsTo(CompraItem::class, 'compra_item_id');
    }

    public function compraRecepcion()
    {
        return $this->belongsTo(CompraRecepcion::class, 'compra_recepcion_id');
    }

    public function compraRecepcionItem()
    {
        return $this->belongsTo(CompraRecepcionItem::class, 'compra_recepcion_item_id');
    }

    public function reposicionProveedor()
    {
        return $this->belongsTo(
            ProveedorReclamoReposicion::class,
            'proveedor_reclamo_reposicion_id'
        );
    }
}
