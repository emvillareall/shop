<?php

namespace App\Livewire\Ecommerce;

use App\Services\Inventario\InventarioService;
use App\Services\Inventario\StockReservaService;
use Livewire\Component;

class CarritoCompras extends Component
{
    public array $items = [];
    public int $secondsRemaining = 0;
    protected InventarioService $inventarioService;
    protected StockReservaService $stockReservaService;

    public function boot(InventarioService $inventarioService, StockReservaService $stockReservaService): void
    {
        $this->inventarioService = $inventarioService;
        $this->stockReservaService = $stockReservaService;
    }

    public function mount(): void
    {
        $this->items = session()->get('carrito', []);
        if (!empty($this->items)) {
            $this->stockReservaService->syncFromCart(session()->getId(), $this->items);
        }
        $this->refreshReservationClock();
    }

    public function updateCantidad(int $index, int $cantidad): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $maxStock = $this->inventarioService->stockDisponibleVariante(
            (int) ($item['producto_id'] ?? 0),
            (int) ($item['color_id'] ?? 0),
            (string) ($item['talla'] ?? '')
        );

        if ($maxStock < 1) {
            $this->removeItem($index);
            return;
        }

        $this->items[$index]['cantidad'] = min(max(1, $cantidad), $maxStock);
        session()->put('carrito', $this->items);
        $this->stockReservaService->syncFromCart(session()->getId(), $this->items);
        $this->refreshReservationClock();
    }

    public function removeItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        session()->put('carrito', $this->items);
        if (empty($this->items)) {
            $this->stockReservaService->releaseSession(session()->getId());
        } else {
            $this->stockReservaService->syncFromCart(session()->getId(), $this->items);
        }
        $this->refreshReservationClock();
    }

    public function clear(): void
    {
        $this->items = [];
        session()->forget('carrito');
        $this->stockReservaService->releaseSession(session()->getId());
        $this->refreshReservationClock();
    }

    public function tickReserva(): void
    {
        $this->refreshReservationClock();
        if ($this->secondsRemaining === 0 && !empty($this->items)) {
            $this->clear();
        }
    }

    private function refreshReservationClock(): void
    {
        $this->secondsRemaining = $this->stockReservaService->secondsRemaining();
    }

    public function getTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            return ((float) ($item['precio'] ?? 0)) * ((int) ($item['cantidad'] ?? 0));
        });
    }

    public function render()
    {
        return view('livewire.ecommerce.carrito-compras');
    }
}
