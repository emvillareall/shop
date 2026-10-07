<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraRecepcionItem extends Model
{
    protected $table = 'compra_recepcion_items';

    protected $fillable = [
        'compra_recepcion_id',
        'compra_item_id',
        'cantidad_recibida',
        'cantidad_aceptada',
        'cantidad_danada',
        'cantidad_incorrecta',
        'cantidad_faltante_declarada',
        'cierra_item',
        'observacion',
    ];

    protected $casts = [
        'cantidad_recibida' => 'integer',
        'cantidad_aceptada' => 'integer',
        'cantidad_danada' => 'integer',
        'cantidad_incorrecta' => 'integer',
        'cantidad_faltante_declarada' => 'integer',
        'cierra_item' => 'boolean',
    ];

    public function compraRecepcion()
    {
        return $this->belongsTo(CompraRecepcion::class, 'compra_recepcion_id');
    }

    public function compraItem()
    {
        return $this->belongsTo(CompraItem::class, 'compra_item_id');
    }

    public function reclamoItems()
    {
        return $this->hasMany(ProveedorReclamoItem::class, 'compra_recepcion_item_id');
    }
}
