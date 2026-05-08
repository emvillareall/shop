<?php

namespace App\Observers;

use App\Models\Pago;
use App\Services\Auditoria\AuditService;

class PagoObserver
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function created(Pago $pago): void
    {
        $this->auditService->log(
            'pago.creado',
            'pagos',
            $pago->id,
            null,
            $pago->toArray()
        );
    }

    public function updated(Pago $pago): void
    {
        $changes = $pago->getChanges();
        $tracked = array_intersect_key($changes, array_flip([
            'estado',
            'referencia_externa',
            'revisado_por',
            'aprobado_at',
            'rechazado_at',
        ]));
        if (empty($tracked)) {
            return;
        }

        $before = [];
        foreach ($tracked as $key => $value) {
            $before[$key] = $pago->getOriginal($key);
        }

        $this->auditService->log(
            'pago.actualizado',
            'pagos',
            $pago->id,
            $before,
            $tracked
        );
    }
}

