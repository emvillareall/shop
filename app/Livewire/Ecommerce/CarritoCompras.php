<?php

namespace App\Livewire\Ecommerce;

use App\Services\Inventario\InventarioService;
use App\Services\Inventario\StockReservaService;
use App\Models\Producto;
use Livewire\Component;

class CarritoCompras extends Component
{
    public array $items = [];
    public array $productImages = [];
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
        $this->reconcileCart();
        $this->refreshReservationClock();
    }

    public function updateCantidad(int $index, int $cantidad): void
    {
        if (!$this->reconcileCart()) {
            return;
        }

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
        if (!$this->reconcileCart()) {
            return;
        }
        $this->refreshReservationClock();
    }

    public function removeItem(int $index): void
    {
        if (!$this->reconcileCart()) {
            return;
        }

        if (!isset($this->items[$index])) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        session()->put('carrito', $this->items);
        $this->reconcileCart();
        $this->refreshReservationClock();
    }

    public function clear(): void
    {
        $this->items = [];
        $this->productImages = [];
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

    private function reconcileCart(): bool
    {
        $storedItems = session()->get('carrito', []);
        $visibleItems = $this->stockReservaService->filterVisibleEcommerceItems($storedItems);
        $removedUnavailable = count($visibleItems) !== count($storedItems);
        $this->items = $visibleItems;

        try {
            if (empty($visibleItems)) {
                session()->forget('carrito');
                $this->stockReservaService->releaseSession(session()->getId());
            } else {
                session()->put('carrito', $visibleItems);
                $this->stockReservaService->syncFromCart(session()->getId(), $visibleItems);
            }
        } catch (\Throwable) {
            $this->items = [];
            $this->productImages = [];
            session()->forget('carrito');
            $this->stockReservaService->releaseSession(session()->getId());
            $this->addError('carrito', 'Uno o más productos de tu carrito ya no están disponibles.');
            return false;
        }

        if ($removedUnavailable) {
            $this->addError('carrito', 'Uno o más productos de tu carrito ya no están disponibles.');
        }

        $this->refreshItemImages();

        return true;
    }

    private function refreshItemImages(): void
    {
        $imageKeys = [];
        $productIdsByImageKey = [];
        $colorIdsByImageKey = [];

        foreach ($this->items as $item) {
            $productId = (int) ($item['producto_id'] ?? 0);
            $colorId = (int) ($item['color_id'] ?? 0);
            if ($productId < 1) {
                continue;
            }

            $imageKey = $productId . ':' . $colorId;
            $imageKeys[$imageKey] = true;
            $productIdsByImageKey[$imageKey] = $productId;
            $colorIdsByImageKey[$imageKey] = $colorId;
        }

        $this->productImages = array_intersect_key($this->productImages, $imageKeys);
        $missingImageKeys = array_diff_key($imageKeys, $this->productImages);
        if ($missingImageKeys === []) {
            return;
        }

        $products = Producto::query()
            ->whereKey(collect(array_keys($missingImageKeys))
                ->map(fn ($key) => $productIdsByImageKey[$key])
                ->unique()
                ->all())
            ->get()
            ->keyBy('id');

        foreach (array_keys($missingImageKeys) as $imageKey) {
            $product = $products->get($productIdsByImageKey[$imageKey]);
            if (!$product) {
                continue;
            }

            $imageUrl = null;
            try {
                $imageUrl = $product->imageUrl($colorIdsByImageKey[$imageKey] ?: null);
            } catch (\Throwable) {
                // An image lookup must never prevent the cart from rendering.
            }

            $this->productImages[$imageKey] = is_string($imageUrl)
                && $imageUrl !== ''
                && !str_starts_with($imageUrl, 'data:')
                    ? $imageUrl
                    : null;
        }
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
