<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'proveedor',
        'evento',
        'evento_id',
        'payload',
        'payload_hash',
        'nonce',
        'transmitido_at',
        'firma_valida',
        'procesado',
        'intentos',
        'error',
        'pago_id',
        'procesado_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'firma_valida' => 'boolean',
        'procesado' => 'boolean',
        'intentos' => 'integer',
        'transmitido_at' => 'datetime',
        'procesado_at' => 'datetime',
    ];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }
}
