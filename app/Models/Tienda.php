<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tienda
 *
 * @property $id
 * @property $nombre_tienda
 * @property $nombres_dueno_tienda
 * @property $apellidos_dueno_tienda
 * @property $cedula_dueno_tienda
 * @property $telefono_dueno_tienda
 * @property $direccion_dueno_tienda
 * @property $email_dueno_tienda
 * @property $estado_tienda
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tienda extends Model
{
    
    static $rules = [
		'nombre_tienda' => 'required',
		'nombres_dueno_tienda' => 'required',
		'apellidos_dueno_tienda' => 'required',
		'cedula_dueno_tienda' => 'required',
		'telefono_dueno_tienda' => 'required',
		'direccion_dueno_tienda' => 'required',
        'estado_tienda',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre_tienda','nombres_dueno_tienda','apellidos_dueno_tienda','cedula_dueno_tienda','telefono_dueno_tienda','direccion_dueno_tienda','email_dueno_tienda','estado_tienda'];



}
