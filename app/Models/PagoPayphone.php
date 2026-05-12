<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoPayphone extends Model
{
    protected $table = 'pagos_payphone';

    protected $fillable = [
        'pago_id',
        'client_transaction_id',
        'payphone_id',
        'transaction_id',
        'authorization_code',
        'payphone_status',
        'status_code',
        'transaction_status',
        'raw_request_json',
        'raw_response_json',
        'confirmed_at',
    ];

    protected $casts = [
        'raw_request_json' => 'array',
        'raw_response_json' => 'array',
        'confirmed_at' => 'datetime',
    ];
}
