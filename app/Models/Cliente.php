<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Cliente
 *
 * @property $id
 * @property $nombres_clientes
 * @property $apellidos_clientes
 * @property $cedula_clientes
 * @property $telefono_clientes
 * @property $ciudad_clientes
 * @property $direccion_clientes
 * @property $email_clientes
 * @property $estado_clientes
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Cliente extends Model
{
    
    static $rules = [
		'nombres_clientes' => 'required',
		'apellidos_clientes' => 'required',
		'cedula_clientes' => 'required',
		'telefono_clientes' => 'required',
		'ciudad_clientes' => 'required',
		'direccion_clientes' => 'required',
        'estado_clientes',
        'email_clientes' ,
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nombres_clientes','apellidos_clientes','cedula_clientes','telefono_clientes','ciudad_clientes','direccion_clientes','estado_clientes' ,'email_clientes'];



}
