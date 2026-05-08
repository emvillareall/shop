<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LineasRopa
 *
 * @property $id
 * @property $nombre_linea
 * @property $estado_linea
 * @property $created_at
 * @property $updated_at
 *
 * @property CategoriasProducto[] $categoriasProductos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class LineasRopa extends Model
{
    
    protected $table = 'lineas_ropa';
    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre_linea'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categoriasProductos()
    {
        return $this->hasMany(\App\Models\CategoriasProducto::class, 'id', 'linea_ropa_id');
    }
    

}
