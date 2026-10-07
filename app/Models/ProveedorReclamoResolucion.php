<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProveedorReclamoResolucion extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'proveedor_reclamo_resoluciones';

    public const TIPO_REPOSICION = 'REPOSICION';
    public const TIPO_NOTA_CREDITO = 'NOTA_CREDITO';
    public const TIPO_REEMBOLSO = 'REEMBOLSO';
    public const TIPO_ACEPTADO_SIN_COMPENSACION = 'ACEPTADO_SIN_COMPENSACION';
    public const TIPO_OTRO = 'OTRO';

    public const TIPOS_RESOLUCION = [
        self::TIPO_REPOSICION,
        self::TIPO_NOTA_CREDITO,
        self::TIPO_REEMBOLSO,
        self::TIPO_ACEPTADO_SIN_COMPENSACION,
        self::TIPO_OTRO,
    ];

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_EN_GESTION = 'EN_GESTION';
    public const ESTADO_RESUELTA = 'RESUELTA';
    public const ESTADO_CANCELADA = 'CANCELADA';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_GESTION,
        self::ESTADO_RESUELTA,
        self::ESTADO_CANCELADA,
    ];

    protected $fillable = [
        'proveedor_reclamo_item_id',
        'tipo_resolucion',
        'cantidad',
        'estado',
        'referencia',
        'observacion',
        'resuelto_por',
        'importe',
        'moneda',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'importe' => 'decimal:2',
    ];

    public function reclamoItem(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamoItem::class, 'proveedor_reclamo_item_id');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function reposiciones(): HasMany
    {
        return $this->hasMany(ProveedorReclamoReposicion::class, 'proveedor_reclamo_resolucion_id');
    }
}
