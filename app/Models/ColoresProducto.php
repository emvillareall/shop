<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ColoresProducto
 *
 * @property $id
 * @property $producto_id
 * @property $colores_id
 * @property $cantidad_por_color
 * @property $stock_por_color
 * @property $created_at
 * @property $updated_at
 *
 * @property Colore $colore
 * @property Producto $producto
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class ColoresProducto extends Model
{
    
    static $rules = [
		'producto_id' => 'required',
		'colores_id',
		'cantidad_por_color' => 'required',
        'stock_por_color' => 'required',
        'talla_por_color' => 'required',
        'created_at',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
   protected $fillable = [
    'producto_id',
    'colores_id',
    'talla_por_color',
    'cantidad_por_color',
    'stock_por_color',
];    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function producto()
    {
        return $this->hasOne('App\Models\Producto', 'id', 'producto_id');
    }

public function color()
{
    return $this->belongsTo(Colores::class, 'colores_id');
}
    

}
