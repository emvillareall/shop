<?php

namespace App\Services\Inventario;

use App\Models\ColoresProducto;
use App\Models\CompraItem;
use App\Models\InventarioMovimiento;
use App\Models\DetallePedido;
use App\Models\Producto;

class InventarioService
{
    public function stockDisponibleVariante(int $productoId, int $colorId, string $talla): int
    {
        $variante = ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->where('colores_id', $colorId)
            ->where('talla_por_color', $talla)
            ->first();

        return $variante ? max(0, (int) $variante->stock_por_color) : 0;
    }

    public function descontarVariante(int $productoId, int $colorId, string $talla, int $cantidad, ?int $pedidoId = null): void
    {
        $variante = ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->where('colores_id', $colorId)
            ->where('talla_por_color', $talla)
            ->lockForUpdate()
            ->first();

        if (!$variante) {
            throw new \RuntimeException('La variante seleccionada no existe.');
        }

        $stockActual = (int) $variante->stock_por_color;
        if ($stockActual < $cantidad) {
            throw new \RuntimeException('Stock insuficiente para la variante seleccionada.');
        }

        $stockAntes = $stockActual;
        $variante->decrement('stock_por_color', $cantidad);
        $stockDespues = max(0, $stockAntes - $cantidad);

        InventarioMovimiento::query()->create([
            'producto_id' => $productoId,
            'colores_productos_id' => $variante->id,
            'pedido_id' => $pedidoId,
            'user_id' => auth()->id(),
            'tipo_movimiento' => 'SALIDA_VENTA',
            'cantidad' => -abs($cantidad),
            'stock_antes' => $stockAntes,
            'stock_despues' => $stockDespues,
            'motivo' => 'Descuento por venta',
            'metadata' => [
                'color_id' => $colorId,
                'talla' => $talla,
            ],
        ]);

        $this->sincronizarStockProducto($productoId);
    }

    public function sincronizarStockProducto(int $productoId): void
    {
        $stockTotal = (int) ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->sum('stock_por_color');

        Producto::query()
            ->where('id', $productoId)
            ->update(['stock_venta_producto' => max(0, $stockTotal)]);
    }

    public function registrarEntradaVariante(
        int $productoId,
        int $colorId,
        string $talla,
        int $cantidad,
        ?int $compraId = null,
        ?int $compraItemId = null,
        ?string $motivo = null
    ): void {
        if ($cantidad <= 0) {
            return;
        }

        $variante = ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->where('colores_id', $colorId)
            ->where('talla_por_color', $talla)
            ->lockForUpdate()
            ->first();

        if (!$variante) {
            throw new \RuntimeException('No se pudo registrar entrada: variante inexistente.');
        }

        $stockAntes = (int) $variante->stock_por_color;
        $variante->increment('stock_por_color', $cantidad);
        $stockDespues = $stockAntes + $cantidad;

        InventarioMovimiento::query()->create([
            'producto_id' => $productoId,
            'colores_productos_id' => $variante->id,
            'compra_id' => $compraId,
            'user_id' => auth()->id(),
            'tipo_movimiento' => 'ENTRADA_COMPRA',
            'cantidad' => abs($cantidad),
            'stock_antes' => $stockAntes,
            'stock_despues' => $stockDespues,
            'motivo' => $motivo ?: 'Entrada por compra',
            'metadata' => [
                'color_id' => $colorId,
                'talla' => $talla,
                'compra_item_id' => $compraItemId,
            ],
        ]);

        $this->sincronizarStockProducto($productoId);
    }

    public function revertirSalidaPedido(int $pedidoId): int
    {
        $detalles = DetallePedido::query()
            ->where('pedido_id', $pedidoId)
            ->where('estado_dtpedidos', 1)
            ->get();

        $revertidos = 0;
        foreach ($detalles as $detalle) {
            $variante = ColoresProducto::query()
                ->where('producto_id', $detalle->producto_id)
                ->where('colores_id', $detalle->id_color_producto)
                ->where('talla_por_color', $detalle->talla_por_color)
                ->lockForUpdate()
                ->first();

            if (!$variante) {
                continue;
            }

            $yaRevertido = InventarioMovimiento::query()
                ->where('tipo_movimiento', 'REVERSO_CANCELACION')
                ->where('pedido_id', $pedidoId)
                ->where('colores_productos_id', $variante->id)
                ->whereJsonContains('metadata->detalle_id', $detalle->id)
                ->exists();
            if ($yaRevertido) {
                continue;
            }

            $cantidad = max(0, (int) $detalle->cantidad_producto);
            if ($cantidad === 0) {
                continue;
            }

            $stockAntes = (int) $variante->stock_por_color;
            $variante->increment('stock_por_color', $cantidad);
            $stockDespues = $stockAntes + $cantidad;

            InventarioMovimiento::query()->create([
                'producto_id' => (int) $detalle->producto_id,
                'colores_productos_id' => $variante->id,
                'pedido_id' => $pedidoId,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'REVERSO_CANCELACION',
                'cantidad' => $cantidad,
                'stock_antes' => $stockAntes,
                'stock_despues' => $stockDespues,
                'motivo' => 'Reverso por cancelacion de pedido',
                'metadata' => [
                    'detalle_id' => $detalle->id,
                    'color_id' => $detalle->id_color_producto,
                    'talla' => $detalle->talla_por_color,
                ],
            ]);

            $this->sincronizarStockProducto((int) $detalle->producto_id);
            $revertidos++;
        }

        return $revertidos;
    }

    public function ajustarStockVariante(
        int $productoId,
        int $colorId,
        string $talla,
        int $deltaCantidad,
        ?int $compraId = null,
        ?int $pedidoId = null,
        ?string $motivo = null
    ): void {
        if ($deltaCantidad === 0) {
            return;
        }

        $variante = ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->where('colores_id', $colorId)
            ->where('talla_por_color', $talla)
            ->lockForUpdate()
            ->first();

        if (!$variante) {
            throw new \RuntimeException('No se pudo ajustar inventario: variante inexistente.');
        }

        $stockAntes = (int) $variante->stock_por_color;
        $stockDespues = $stockAntes + $deltaCantidad;
        if ($stockDespues < 0) {
            throw new \RuntimeException('Ajuste invalido: el stock no puede quedar negativo.');
        }

        $variante->update(['stock_por_color' => $stockDespues]);

        InventarioMovimiento::query()->create([
            'producto_id' => $productoId,
            'colores_productos_id' => $variante->id,
            'pedido_id' => $pedidoId,
            'compra_id' => $compraId,
            'user_id' => auth()->id(),
            'tipo_movimiento' => 'AJUSTE',
            'cantidad' => $deltaCantidad,
            'stock_antes' => $stockAntes,
            'stock_despues' => $stockDespues,
            'motivo' => $motivo ?: 'Ajuste manual de inventario',
            'metadata' => [
                'color_id' => $colorId,
                'talla' => $talla,
            ],
        ]);

        $this->sincronizarStockProducto($productoId);
    }

    public function revertirCompra(int $compraId): int
    {
        $items = CompraItem::query()
            ->where('compra_id', $compraId)
            ->where('estado', 'ACTIVO')
            ->get();

        $revertidos = 0;
        foreach ($items as $item) {
            $colorId = (int) ($item->colores_id ?? 0);
            $talla = (string) ($item->talla ?? '');
            if (!$colorId || $talla === '') {
                continue;
            }

            $yaRevertido = InventarioMovimiento::query()
                ->where('tipo_movimiento', 'REVERSO_COMPRA')
                ->where('compra_id', $compraId)
                ->whereJsonContains('metadata->compra_item_id', (int) $item->id)
                ->exists();
            if ($yaRevertido) {
                continue;
            }

            $variante = ColoresProducto::query()
                ->where('producto_id', (int) $item->producto_id)
                ->where('colores_id', $colorId)
                ->where('talla_por_color', $talla)
                ->lockForUpdate()
                ->first();

            if (!$variante) {
                continue;
            }

            $saldoEntrada = (int) InventarioMovimiento::query()
                ->where('tipo_movimiento', 'ENTRADA_COMPRA')
                ->where('compra_id', $compraId)
                ->where('colores_productos_id', $variante->id)
                ->whereJsonContains('metadata->compra_item_id', (int) $item->id)
                ->sum('cantidad');

            $yaRevertidoCantidad = abs((int) InventarioMovimiento::query()
                ->where('tipo_movimiento', 'REVERSO_COMPRA')
                ->where('compra_id', $compraId)
                ->where('colores_productos_id', $variante->id)
                ->whereJsonContains('metadata->compra_item_id', (int) $item->id)
                ->sum('cantidad'));

            $pendiente = max(0, $saldoEntrada - $yaRevertidoCantidad);
            if ($pendiente <= 0) {
                continue;
            }

            $stockAntes = (int) $variante->stock_por_color;
            if ($stockAntes < $pendiente) {
                throw new \RuntimeException(
                    "No se puede anular compra {$compraId}: stock actual insuficiente para revertir item {$item->id}."
                );
            }

            $stockDespues = $stockAntes - $pendiente;
            $variante->update(['stock_por_color' => $stockDespues]);

            InventarioMovimiento::query()->create([
                'producto_id' => (int) $item->producto_id,
                'colores_productos_id' => $variante->id,
                'compra_id' => $compraId,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'REVERSO_COMPRA',
                'cantidad' => -$pendiente,
                'stock_antes' => $stockAntes,
                'stock_despues' => $stockDespues,
                'motivo' => 'Reverso por anulacion de compra',
                'metadata' => [
                    'compra_item_id' => (int) $item->id,
                    'color_id' => $colorId,
                    'talla' => $talla,
                ],
            ]);

            $this->sincronizarStockProducto((int) $item->producto_id);
            $item->update(['estado' => 'ANULADO']);
            $revertidos++;
        }

        return $revertidos;
    }
}
