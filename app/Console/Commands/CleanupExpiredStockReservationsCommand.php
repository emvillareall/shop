<?php

namespace App\Console\Commands;

use App\Services\Inventario\StockReservaService;
use Illuminate\Console\Command;

class CleanupExpiredStockReservationsCommand extends Command
{
    protected $signature = 'stock:reservas:cleanup';
    protected $description = 'Limpia reservas de stock expiradas';

    public function handle(StockReservaService $stockReservaService): int
    {
        $deleted = $stockReservaService->releaseExpired();
        $this->info("Reservas expiradas eliminadas: {$deleted}");
        return self::SUCCESS;
    }
}

