<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoTransferencia extends Model
{
    protected $table = 'pagos_transferencias';

    protected $fillable = [
        'pago_id',
        'banco_origen',
        'titular_origen',
        'numero_referencia',
        'fecha_transferencia',
    ];

    protected $casts = [
        'fecha_transferencia' => 'datetime',
    ];
}

