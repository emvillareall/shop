<?php

namespace App\Providers;

use App\Models\InventarioMovimiento;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\CompraItem;
use App\Observers\CompraItemObserver;
use App\Observers\InventarioMovimientoObserver;
use App\Observers\PagoObserver;
use App\Observers\PedidoObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        Pedido::observe(PedidoObserver::class);
        Pago::observe(PagoObserver::class);
        InventarioMovimiento::observe(InventarioMovimientoObserver::class);
        CompraItem::observe(CompraItemObserver::class);
    }
}
