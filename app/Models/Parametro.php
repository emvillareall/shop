<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Parametro
 *
 * @property $id
 * @property $nombre_parametro
 * @property $valor_parametro
 * @property $estado_parametro
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Parametro extends Model
{
    
    static $rules = [
		'nombre_parametro' => 'required',
		'valor_parametro' => 'required',
        'estado_parametro'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre_parametro','valor_parametro','estado_parametro'];



}
