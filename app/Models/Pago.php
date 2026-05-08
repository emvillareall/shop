<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'pedido_id',
        'metodo',
        'estado',
        'monto',
        'moneda',
        'referencia_externa',
        'comprobante_path',
        'observacion',
        'metadata',
        'revisado_por',
        'revisado_at',
        'aprobado_at',
        'rechazado_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'revisado_at' => 'datetime',
        'aprobado_at' => 'datetime',
        'rechazado_at' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function transferencia()
    {
        return $this->hasOne(PagoTransferencia::class, 'pago_id');
    }
}

