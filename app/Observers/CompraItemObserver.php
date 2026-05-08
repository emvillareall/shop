<?php

namespace App\Observers;

use App\Models\CompraItem;
use App\Services\Auditoria\AuditService;

class CompraItemObserver
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function created(CompraItem $item): void
    {
        $this->auditService->log(
            'compra_item.creado',
            'compra_items',
            $item->id,
            null,
            $item->toArray()
        );
    }
}

