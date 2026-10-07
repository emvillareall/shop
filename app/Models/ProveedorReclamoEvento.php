<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProveedorReclamoEvento extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'proveedor_reclamo_eventos';

    public const ACCION_RECLAMO_CREADO = 'RECLAMO_CREADO';
    public const ACCION_LINEA_AGREGADA = 'LINEA_AGREGADA';
    public const ACCION_RECLAMO_ENVIADO = 'RECLAMO_ENVIADO';
    public const ACCION_RECLAMO_EN_GESTION = 'RECLAMO_EN_GESTION';
    public const ACCION_LINEA_CANCELADA = 'LINEA_CANCELADA';
    public const ACCION_RECLAMO_CANCELADO = 'RECLAMO_CANCELADO';
    public const ACCION_RESOLUCION_REGISTRADA = 'RESOLUCION_REGISTRADA';
    public const ACCION_RESOLUCION_EN_GESTION = 'RESOLUCION_EN_GESTION';
    public const ACCION_RESOLUCION_RESUELTA = 'RESOLUCION_RESUELTA';
    public const ACCION_RESOLUCION_CANCELADA = 'RESOLUCION_CANCELADA';
    public const ACCION_REPOSICION_RECIBIDA = 'REPOSICION_RECIBIDA';
    public const ACCION_LINEA_EN_GESTION = 'LINEA_EN_GESTION';
    public const ACCION_LINEA_RESUELTA = 'LINEA_RESUELTA';
    public const ACCION_LINEA_REABIERTA = 'LINEA_REABIERTA';
    public const ACCION_RECLAMO_RESUELTO = 'RECLAMO_RESUELTO';

    protected $fillable = [
        'proveedor_reclamo_id',
        'proveedor_reclamo_item_id',
        'proveedor_reclamo_resolucion_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'observacion',
        'fecha_evento',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
    ];

    public function reclamo(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamo::class, 'proveedor_reclamo_id');
    }

    public function reclamoItem(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamoItem::class, 'proveedor_reclamo_item_id');
    }

    public function reclamoResolucion(): BelongsTo
    {
        return $this->belongsTo(ProveedorReclamoResolucion::class, 'proveedor_reclamo_resolucion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
