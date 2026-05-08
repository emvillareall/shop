<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoPayphone extends Model
{
    protected $table = 'pagos_payphone';

    protected $fillable = [
        'pago_id',
        'transaction_id',
        'authorization_code',
        'payphone_status',
    ];
}

