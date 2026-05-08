<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Pedido
 *
 * @property $id
 * @property $clientes_id
 * @property $tienda_id
 * @property $created_at
 * @property $updated_at
 * @property $descripcion
 * @property $estado_url
 * @property $estado_pedidos
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Pedido extends Model
{
    public const ESTADO_PEDIDO_LABELS = [
        'BORRADOR' => 'Borrador',
        'PENDIENTE_PAGO' => 'Pendiente de pago',
        'PAGO_EN_REVISION' => 'Pago en revision',
        'PAGADO' => 'Pagado',
        'EN_PREPARACION' => 'En preparacion',
        'DESPACHADO' => 'Despachado',
        'ENTREGADO' => 'Entregado',
        'CANCELADO' => 'Cancelado',
        'RECHAZADO' => 'Rechazado',
        'DEVUELTO' => 'Devuelto',
    ];

    public const ESTADO_PAGO_LABELS = [
        'SIN_PAGO' => 'Sin pago',
        'PENDIENTE' => 'Pendiente',
        'EN_REVISION' => 'En revision',
        'APROBADO' => 'Aprobado',
        'RECHAZADO' => 'Rechazado',
        'REEMBOLSADO' => 'Reembolsado',
    ];

    public const ESTADO_ENVIO_LABELS = [
        'SIN_ENVIO' => 'Sin envio',
        'PENDIENTE' => 'Pendiente',
        'PREPARANDO' => 'Preparando',
        'ENVIADO' => 'Enviado',
        'ENTREGADO' => 'Entregado',
        'NO_ENTREGADO' => 'No entregado',
    ];
    
    static $rules = [
		'clientes_id',
		'tienda_id' ,
        'descripcion',
        'estado_url',
        'subtotal_pedido', 
        'descuentos_pedido',
        'estado_pedidos'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo_pedido',
        'clientes_id',
        'tienda_id',
        'descripcion',
        'estado_url',
        'estado_pedidos',
        'estado_pedido',
        'estado_pago',
        'estado_envio',
        'subtotal_pedido',
        'descuentos_pedido',
        'confirmado_at',
        'pagado_at',
        'despachado_at',
        'cancelado_at',
    ];

    protected $casts = [
        'confirmado_at' => 'datetime',
        'pagado_at' => 'datetime',
        'despachado_at' => 'datetime',
        'cancelado_at' => 'datetime',
    ];

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'pedido_id');
    }

    public function estadoPedidoNormalizado(): string
    {
        if (!empty($this->estado_pedido)) {
            return (string) $this->estado_pedido;
        }

        if ((string) $this->estado_url === 'ENVIADO') {
            return 'DESPACHADO';
        }

        return 'PENDIENTE_PAGO';
    }

    public function estadoPagoNormalizado(): string
    {
        if (!empty($this->estado_pago)) {
            return (string) $this->estado_pago;
        }

        return 'SIN_PAGO';
    }

    public function estadoEnvioNormalizado(): string
    {
        if (!empty($this->estado_envio)) {
            return (string) $this->estado_envio;
        }

        return (string) $this->estado_url === 'ENVIADO' ? 'ENVIADO' : 'PENDIENTE';
    }

    public function estadoPedidoLabel(): string
    {
        $estado = $this->estadoPedidoNormalizado();
        return self::ESTADO_PEDIDO_LABELS[$estado] ?? str_replace('_', ' ', $estado);
    }

    public function estadoPagoLabel(): string
    {
        $estado = $this->estadoPagoNormalizado();
        return self::ESTADO_PAGO_LABELS[$estado] ?? str_replace('_', ' ', $estado);
    }

    public function estadoEnvioLabel(): string
    {
        $estado = $this->estadoEnvioNormalizado();
        return self::ESTADO_ENVIO_LABELS[$estado] ?? str_replace('_', ' ', $estado);
    }

    public function estadoPedidoBadgeClass(): string
    {
        return match ($this->estadoPedidoNormalizado()) {
            'PAGADO', 'ENTREGADO' => 'bg-success',
            'PENDIENTE_PAGO', 'PAGO_EN_REVISION', 'EN_PREPARACION' => 'bg-warning',
            'CANCELADO', 'RECHAZADO' => 'bg-danger',
            default => 'bg-primary',
        };
    }

    public function estadoPagoBadgeClass(): string
    {
        return match ($this->estadoPagoNormalizado()) {
            'APROBADO' => 'bg-success',
            'EN_REVISION', 'PENDIENTE' => 'bg-warning',
            'RECHAZADO' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function estadoEnvioBadgeClass(): string
    {
        return match ($this->estadoEnvioNormalizado()) {
            'ENTREGADO', 'ENVIADO' => 'bg-success',
            'PENDIENTE', 'PREPARANDO' => 'bg-warning',
            'NO_ENTREGADO' => 'bg-danger',
            default => 'bg-info',
        };
    }



}
