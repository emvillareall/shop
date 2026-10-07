<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayphoneToolLink extends Model
{
    protected $fillable = [
        'client_transaction_id',
        'base_amount_cents',
        'surcharge_percent',
        'surcharge_amount_cents',
        'total_amount_cents',
        'currency',
        'reference',
        'payment_url',
        'status',
        'link_expires_at',
        'requester_ip',
    ];

    protected $casts = [
        'base_amount_cents' => 'integer',
        'surcharge_percent' => 'decimal:2',
        'surcharge_amount_cents' => 'integer',
        'total_amount_cents' => 'integer',
        'payment_url' => 'encrypted',
        'link_expires_at' => 'datetime',
    ];
}
