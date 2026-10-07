<?php

namespace App\Services\Inventario;

use App\Models\ColoresProducto;
use App\Models\Producto;
use App\Models\StockReserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockReservaService
{
    public const SESSION_KEY_EXPIRES = 'cart_reservation_expires_at';
    public const TTL_MINUTES = 15;

    public function syncFromCart(string $sessionId, array $cartItems): void
    {
        $this->assertVisibleEcommerceCart($cartItems);
        $this->releaseExpired();

        DB::transaction(function () use ($sessionId, $cartItems) {
            // Keep publication and reservation decisions under the same product
            // lock used by the administrative publication action.
            $this->assertVisibleEcommerceCart($cartItems, true);

            $expiresAt = now()->addMinutes(self::TTL_MINUTES);
            $expected = $this->aggregateCart($cartItems);

            $existing = StockReserva::query()
                ->where('session_id', $sessionId)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (StockReserva $r) => $this->key((int) $r->producto_id, (int) $r->color_id, (string) $r->talla));

            foreach ($expected as $key => $row) {
                $variante = ColoresProducto::query()
                    ->where('producto_id', $row['producto_id'])
                    ->where('colores_id', $row['color_id'])
                    ->where('talla_por_color', $row['talla'])
                    ->lockForUpdate()
                    ->first();

                if (!$variante) {
                    throw new \RuntimeException('La variante seleccionada ya no existe.');
                }

                $reservadoPorOtros = (int) StockReserva::query()
                    ->where('producto_id', $row['producto_id'])
                    ->where('color_id', $row['color_id'])
                    ->where('talla', $row['talla'])
                    ->where('session_id', '!=', $sessionId)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->sum('cantidad');

                $stockReal = (int) $variante->stock_por_color;
                $disponible = max(0, $stockReal - $reservadoPorOtros);
                if ($row['cantidad'] > $disponible) {
                    throw new \RuntimeException('Stock reservado insuficiente para una o más variantes.');
                }

                StockReserva::query()->updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'producto_id' => $row['producto_id'],
                        'color_id' => $row['color_id'],
                        'talla' => $row['talla'],
                    ],
                    [
                        'cantidad' => $row['cantidad'],
                        'expires_at' => $expiresAt,
                        'metadata' => ['source' => 'shop_cart'],
                    ]
                );
            }

            $keysExpected = array_keys($expected);
            foreach ($existing as $key => $reserva) {
                if (!in_array($key, $keysExpected, true)) {
                    $reserva->delete();
                }
            }

            session()->put(self::SESSION_KEY_EXPIRES, $expiresAt->toIso8601String());
        });
    }

    /**
     * Remove from a cart the items that no longer satisfy the public ecommerce rule.
     * Only the supplied cart/session is affected; no other session reservations are touched.
     */
    public function filterVisibleEcommerceItems(array $cartItems): array
    {
        $productIds = collect($cartItems)
            ->map(fn ($item) => (int) ($item['producto_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        $visibleIds = Producto::query()
            ->visibleEcommerce()
            ->whereKey($productIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_filter(
            $cartItems,
            fn ($item) => in_array((int) ($item['producto_id'] ?? 0), $visibleIds, true)
        ));
    }

    /**
     * Validate all cart products against the single public visibility rule.
     * When requested, product locks are acquired in deterministic id order for checkout.
     */
    public function assertVisibleEcommerceCart(array $cartItems, bool $lockProducts = false): void
    {
        $productIds = collect($cartItems)
            ->map(fn ($item) => (int) ($item['producto_id'] ?? 0))
            ->values();

        if ($productIds->contains(fn (int $id) => $id < 1)) {
            throw new \RuntimeException('Uno o más productos de tu carrito ya no están disponibles.');
        }

        $productIds = $productIds->unique()->sort()->values();
        if ($productIds->isEmpty()) {
            return;
        }

        if ($lockProducts) {
            foreach ($productIds as $productId) {
                $visible = Producto::query()
                    ->visibleEcommerce()
                    ->whereKey($productId)
                    ->lockForUpdate()
                    ->first();

                if (!$visible) {
                    throw new \RuntimeException('Uno o más productos de tu carrito ya no están disponibles.');
                }
            }

            return;
        }

        $visibleCount = Producto::query()
            ->visibleEcommerce()
            ->whereKey($productIds->all())
            ->count();

        if ($visibleCount !== $productIds->count()) {
            throw new \RuntimeException('Uno o más productos de tu carrito ya no están disponibles.');
        }
    }

    public function releaseSession(string $sessionId): void
    {
        StockReserva::query()->where('session_id', $sessionId)->delete();
        session()->forget(self::SESSION_KEY_EXPIRES);
    }

    public function releaseExpired(): int
    {
        return StockReserva::query()->where('expires_at', '<=', now())->delete();
    }

    public function secondsRemaining(): int
    {
        $expiresAt = session(self::SESSION_KEY_EXPIRES);
        if (!$expiresAt) {
            return 0;
        }
        $expires = Carbon::parse($expiresAt);
        return max(0, now()->diffInSeconds($expires, false));
    }

    public function assertSessionActive(string $sessionId): void
    {
        $expiresAt = session(self::SESSION_KEY_EXPIRES);
        if (!$expiresAt || now()->greaterThanOrEqualTo(Carbon::parse($expiresAt))) {
            $this->releaseSession($sessionId);
            throw new \RuntimeException('La reserva del carrito expiró. Vuelve a agregar los productos.');
        }
    }

    public function consumeSessionReservations(string $sessionId): void
    {
        StockReserva::query()->where('session_id', $sessionId)->delete();
        session()->forget(self::SESSION_KEY_EXPIRES);
    }

    private function aggregateCart(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $productoId = (int) ($item['producto_id'] ?? 0);
            $colorId = (int) ($item['color_id'] ?? 0);
            $talla = trim((string) ($item['talla'] ?? ''));
            $cantidad = max(0, (int) ($item['cantidad'] ?? 0));
            if ($productoId < 1 || $colorId < 1 || $talla === '' || $cantidad < 1) {
                continue;
            }
            $key = $this->key($productoId, $colorId, $talla);
            if (!isset($out[$key])) {
                $out[$key] = [
                    'producto_id' => $productoId,
                    'color_id' => $colorId,
                    'talla' => $talla,
                    'cantidad' => 0,
                ];
            }
            $out[$key]['cantidad'] += $cantidad;
        }
        return $out;
    }

    private function key(int $productoId, int $colorId, string $talla): string
    {
        return $productoId . '|' . $colorId . '|' . $talla;
    }
}
