<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Services\Auditoria\AuditService;

class PedidoObserver
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function created(Pedido $pedido): void
    {
        $this->auditService->log(
            'pedido.creado',
            'pedidos',
            $pedido->id,
            null,
            $pedido->toArray()
        );
    }

    public function updated(Pedido $pedido): void
    {
        $changes = $pedido->getChanges();
        $tracked = array_intersect_key($changes, array_flip([
            'estado_pedido',
            'estado_pago',
            'estado_envio',
            'estado_url',
            'estado_pedidos',
        ]));

        if (empty($tracked)) {
            return;
        }

        $before = [];
        foreach ($tracked as $key => $value) {
            $before[$key] = $pedido->getOriginal($key);
        }

        $this->auditService->log(
            'pedido.actualizado',
            'pedidos',
            $pedido->id,
            $before,
            $tracked
        );
    }
}

