<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CategoriasProducto
 *
 * @property $id
 * @property $nombre_categoria
 * @property $linea_ropa_id
 * @property $estado_categoria
 * @property $created_at
 * @property $updated_at
 *
 * @property LineasRopa $lineasRopa
 * @property Producto[] $productos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CategoriasProducto extends Model
{
    

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre_categoria', 'linea_ropa_id', 'estado_categoria'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lineasRopa()
    {
        return $this->belongsTo(\App\Models\LineasRopa::class, 'linea_ropa_id', 'id');
    }

    public function linea()
    {
        return $this->belongsTo(\App\Models\LineasRopa::class, 'linea_ropa_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productos()
    {
        return $this->hasMany(\App\Models\Producto::class, 'categoria_producto_id', 'id');
    }
    

}
