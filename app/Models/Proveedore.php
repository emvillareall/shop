<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Proveedore
 *
 * @property $id
 * @property $tienda_proveedor
 * @property $nombres_proveedor
 * @property $apellidos_proveedor
 * @property $cedula_proveedor
 * @property $telefono_proveedor
 * @property $direccion_proveedor
 * @property $email_proveedor
 * @property $estado_proveedor
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Proveedore extends Model
{
    
    static $rules = [
		'tienda_proveedor' => 'required',
		'nombres_proveedor' => 'required',
		'apellidos_proveedor' => 'required',
        'estado_proveedor'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['tienda_proveedor','nombres_proveedor','apellidos_proveedor','cedula_proveedor','telefono_proveedor','direccion_proveedor','estado_proveedor','email_proveedor'];



}
