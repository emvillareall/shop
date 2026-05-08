<?php

namespace App\Services\Inventario;

use App\Models\ColoresProducto;
use App\Models\StockReserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockReservaService
{
    public const SESSION_KEY_EXPIRES = 'cart_reservation_expires_at';
    public const TTL_MINUTES = 15;

    public function syncFromCart(string $sessionId, array $cartItems): void
    {
        $this->releaseExpired();

        DB::transaction(function () use ($sessionId, $cartItems) {
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

