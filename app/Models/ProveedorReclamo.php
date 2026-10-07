<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProveedorReclamo extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'proveedor_reclamos';

    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_ENVIADO = 'ENVIADO';
    public const ESTADO_EN_GESTION = 'EN_GESTION';
    public const ESTADO_RESUELTO = 'RESUELTO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    public const ESTADOS = [
        self::ESTADO_BORRADOR,
        self::ESTADO_ENVIADO,
        self::ESTADO_EN_GESTION,
        self::ESTADO_RESUELTO,
        self::ESTADO_CANCELADO,
    ];

    protected $fillable = [
        'compra_id',
        'creado_por',
        'estado',
        'referencia_proveedor',
        'observacion',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProveedorReclamoItem::class, 'proveedor_reclamo_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(ProveedorReclamoEvento::class, 'proveedor_reclamo_id');
    }
}
