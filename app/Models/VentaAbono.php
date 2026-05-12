<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaAbono extends Model
{
    protected $table = 'venta_abonos';

    protected $fillable = [
        'venta_id',
        'pedido_id',
        'monto',
        'metodo',
        'referencia',
        'observacion',
        'fecha_abono',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_abono' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}

