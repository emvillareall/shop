<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoPaypal extends Model
{
    protected $table = 'pagos_paypal';

    protected $fillable = [
        'pago_id',
        'paypal_order_id',
        'paypal_capture_id',
        'paypal_status',
    ];
}

