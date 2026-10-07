<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProveedorReclamoItem extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'proveedor_reclamo_items';

    public const TIPO_DANADA = 'DANADA';
    public const TIPO_INCORRECTA = 'INCORRECTA';
    public const TIPO_FALTANTE = 'FALTANTE';

    public const TIPOS_INCIDENCIA = [
        self::TIPO_DANADA,
        self::TIPO_INCORRECTA,
        self::TIPO_FALTANTE,
    ];

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_EN_GESTION = 'EN_GESTION';
    public const ESTADO_RESUELTO = 'RESUELTO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_GESTION,
        self::ESTADO_RESUELTO,
        self::ESTADO_CANCELADO,
    ];

    protected $fillable = [
        'proveedor_reclamo_id',
        'compra_recepcion_item_id',
        'tipo_incidencia',
        'cantidad_reclamada',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'cantidad_reclamada' => 'integer',
    ];

    public function reclamo(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamo::class, 'proveedor_reclamo_id');
    }

    public function compraRecepcionItem(): BelongsTo
    {
        return $this->belongsTo(CompraRecepcionItem::class, 'compra_recepcion_item_id');
    }

    public function resoluciones(): HasMany
    {
        return $this->hasMany(ProveedorReclamoResolucion::class, 'proveedor_reclamo_item_id');
    }
}
