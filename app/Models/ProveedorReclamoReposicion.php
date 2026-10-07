<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProveedorReclamoReposicion extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'proveedor_reclamo_reposiciones';

    protected $fillable = [
        'proveedor_reclamo_resolucion_id',
        'recibido_por',
        'idempotency_key',
        'cantidad_recibida',
        'cantidad_aceptada',
        'cantidad_danada',
        'cantidad_incorrecta',
        'observacion',
        'fecha_recepcion',
    ];

    protected $casts = [
        'cantidad_recibida' => 'integer',
        'cantidad_aceptada' => 'integer',
        'cantidad_danada' => 'integer',
        'cantidad_incorrecta' => 'integer',
        'fecha_recepcion' => 'datetime',
    ];

    public function resolucion(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamoResolucion::class, 'proveedor_reclamo_resolucion_id');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'proveedor_reclamo_reposicion_id');
    }
}
