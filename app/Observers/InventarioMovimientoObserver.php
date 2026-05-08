<?php

namespace App\Observers;

use App\Models\InventarioMovimiento;
use App\Services\Auditoria\AuditService;

class InventarioMovimientoObserver
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function created(InventarioMovimiento $movimiento): void
    {
        $this->auditService->log(
            'inventario.movimiento',
            'inventario_movimientos',
            $movimiento->id,
            null,
            $movimiento->toArray()
        );
    }
}

