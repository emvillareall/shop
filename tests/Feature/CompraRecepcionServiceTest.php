<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Services\Compras\CompraRecepcionException;
use App\Services\Compras\CompraRecepcionService;
use App\Services\Inventario\InventarioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CompraRecepcionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createBaseSchema();
        $this->runReceptionMigrations();
    }

    public function test_receives_a_complete_item_and_links_the_inventory_movement(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->recibirCompleta(
            $compra,
            (string) Str::uuid()
        );

        $linea = CompraRecepcionItem::query()->firstOrFail();
        $movimiento = DB::table('inventario_movimientos')->first();

        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame($compra->id, (int) $recepcion->compra_id);
        $this->assertSame($itemId, (int) $linea->compra_item_id);
        $this->assertSame(10, (int) $linea->cantidad_recibida);
        $this->assertSame(10, (int) $linea->cantidad_aceptada);
        $this->assertTrue((bool) $linea->cierra_item);
        $this->assertSame(10, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(10, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame('ENTRADA_COMPRA', $movimiento->tipo_movimiento);
        $this->assertSame(10, (int) $movimiento->cantidad);
        $this->assertSame($itemId, (int) $movimiento->compra_item_id);
        $this->assertSame($recepcion->id, (int) $movimiento->compra_recepcion_id);
        $this->assertSame($linea->id, (int) $movimiento->compra_recepcion_item_id);
    }

    public function test_reception_does_not_publish_an_unpublished_product(): void
    {
        $compra = $this->createPurchase();
        [$productoId] = $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertFalse((bool) DB::table('productos')->where('id', $productoId)->value('publicado_ecommerce'));

        app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid());

        $this->assertSame(10, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertFalse((bool) DB::table('productos')->where('id', $productoId)->value('publicado_ecommerce'));
    }

    public function test_receives_multiple_variants_as_separate_complete_lines(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemS] = $this->createItem($compra, 5, 0, 0, 'S');
        [, $itemM] = $this->createItem($compra, 3, 0, 0, 'M', $productoId);

        $recepcion = app(CompraRecepcionService::class)->recibirCompleta(
            $compra,
            (string) Str::uuid()
        );

        $items = CompraRecepcionItem::query()
            ->orderBy('compra_item_id')
            ->get();
        $movimientos = DB::table('inventario_movimientos')
            ->where('compra_id', $compra->id)
            ->orderBy('cantidad')
            ->get();

        $this->assertSame(1, $recepcion->id);
        $this->assertSame([$itemS, $itemM], $items->pluck('compra_item_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([5, 3], $items->pluck('cantidad_aceptada')->map(fn ($qty) => (int) $qty)->all());
        $this->assertSame(2, $movimientos->count());
        $this->assertSame([3, 5], $movimientos->pluck('cantidad')->map(fn ($qty) => (int) $qty)->all());
        $this->assertSame(8, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame(8, (int) DB::table('colores_productos')->where('producto_id', $productoId)->sum('stock_por_color'));
        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_receiving_increments_existing_physical_stock_instead_of_replacing_it(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 2, 'S');

        app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid());

        $this->assertSame(10, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(12, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(12, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame(10, (int) DB::table('inventario_movimientos')->sum('cantidad'));
    }

    public function test_same_idempotency_key_returns_the_existing_reception_without_duplicate_stock(): void
    {
        $compra = $this->createPurchase();
        [$productoId] = $this->createItem($compra, 10, 0, 0, 'S');
        $key = (string) Str::uuid();
        $service = app(CompraRecepcionService::class);

        $first = $service->recibirCompleta($compra, $key);
        $second = $service->recibirCompleta($compra->fresh(), $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame(1, CompraRecepcionItem::query()->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
    }

    public function test_same_idempotency_key_cannot_be_used_by_another_purchase(): void
    {
        $firstPurchase = $this->createPurchase();
        $secondPurchase = $this->createPurchase();
        $this->createItem($firstPurchase, 4, 0, 0, 'S');
        $this->createItem($secondPurchase, 6, 0, 0, 'M');
        $key = (string) Str::uuid();
        $service = app(CompraRecepcionService::class);

        $service->recibirCompleta($firstPurchase, $key);
        $this->assertReceptionRejected(fn () => $service->recibirCompleta($secondPurchase, $key));

        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame('EN_TRANSITO', DB::table('compras')->where('id', $secondPurchase->id)->value('estado_recepcion'));
    }

    public function test_second_reception_with_a_new_key_is_rejected_without_changes(): void
    {
        $compra = $this->createPurchase();
        [$productoId] = $this->createItem($compra, 10, 0, 0, 'S');
        $service = app(CompraRecepcionService::class);

        $service->recibirCompleta($compra, (string) Str::uuid());
        $this->assertReceptionRejected(fn () => $service->recibirCompleta($compra->fresh(), (string) Str::uuid()));

        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
    }

    public function test_anulada_purchase_is_rejected_without_creating_a_reception(): void
    {
        $compra = $this->createPurchase('ANULADA');
        $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid()));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
    }

    public function test_historical_null_purchase_is_rejected_without_inference(): void
    {
        $compra = $this->createPurchase(null);
        $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid()));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
    }

    public function test_empty_transit_purchase_is_rejected(): void
    {
        $compra = $this->createPurchase();

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid()));

        $this->assertSame('EN_TRANSITO', $compra->fresh()->estado_recepcion);
        $this->assertSame(0, CompraRecepcion::query()->count());
    }

    public function test_failure_on_a_later_item_rolls_back_everything(): void
    {
        $compra = $this->createPurchase();
        [$productoId] = $this->createItem($compra, 5, 0, 0, 'S');
        $this->createItem($compra, 3, 0, 0, 'M', $productoId);

        $realService = new InventarioService();
        $calls = 0;
        $inventoryMock = Mockery::mock(InventarioService::class)->makePartial();
        $inventoryMock->shouldReceive('registrarEntradaVariante')
            ->twice()
            ->andReturnUsing(function (...$args) use (&$calls, $realService) {
                $calls++;
                if ($calls === 2) {
                    throw new \RuntimeException('Falla controlada durante la recepción.');
                }

                return $realService->registrarEntradaVariante(...$args);
            });
        $this->app->instance(InventarioService::class, $inventoryMock);

        try {
            app(CompraRecepcionService::class)->recibirCompleta($compra, (string) Str::uuid());
            $this->fail('Se esperaba una falla controlada durante la recepción.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falla controlada durante la recepción.', $exception->getMessage());
        }

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, CompraRecepcionItem::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->sum('stock_por_color'));
        $this->assertSame(0, (int) DB::table('compra_items')->where('compra_id', $compra->id)->sum('stock_ingresado'));
        $this->assertSame('EN_TRANSITO', $compra->fresh()->estado_recepcion);
    }

    public function test_registers_a_simple_partial_receipt_and_keeps_the_item_open(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 4)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertNotNull($linea);
        $this->assertSame(4, (int) $linea->cantidad_recibida);
        $this->assertSame(4, (int) $linea->cantidad_aceptada);
        $this->assertSame(0, (int) $linea->cantidad_danada);
        $this->assertFalse((bool) $linea->cierra_item);
        $this->assertSame(4, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(4, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('RECEPCION_PARCIAL', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(4, (int) DB::table('inventario_movimientos')->value('cantidad'));
        $this->assertSame(6, $this->pendiente($itemId));
    }

    public function test_second_delivery_closes_the_remaining_pending_quantity(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');
        $service = app(CompraRecepcionService::class);

        $service->registrarRecepcion($compra, [$this->linea($itemId, 4)], (string) Str::uuid());
        $service->registrarRecepcion($compra->fresh(), [$this->linea($itemId, 6, 0, 0, 0, true)], (string) Str::uuid());

        $this->assertSame(2, CompraRecepcion::query()->count());
        $this->assertSame(2, CompraRecepcionItem::query()->count());
        $this->assertSame(10, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(0, $this->pendiente($itemId));
        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame([4, 6], DB::table('inventario_movimientos')->orderBy('id')->pluck('cantidad')->map(fn ($quantity) => (int) $quantity)->all());
    }

    public function test_damaged_units_are_received_but_do_not_enter_stock(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 8, 2, 0, 0, true)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertSame(10, (int) $linea->cantidad_recibida);
        $this->assertSame(8, (int) $linea->cantidad_aceptada);
        $this->assertSame(2, (int) $linea->cantidad_danada);
        $this->assertSame(8, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(8, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(8, (int) DB::table('inventario_movimientos')->value('cantidad'));
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_incorrect_units_do_not_enter_the_requested_variant(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 9, 0, 1, 0, true)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertSame(10, (int) $linea->cantidad_recibida);
        $this->assertSame(1, (int) $linea->cantidad_incorrecta);
        $this->assertSame(9, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(9, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_declared_missing_units_close_the_item_without_stock_for_them(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 9, 0, 0, 1, true)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertSame(9, (int) $linea->cantidad_recibida);
        $this->assertSame(1, (int) $linea->cantidad_faltante_declarada);
        $this->assertSame(9, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(9, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(0, $this->pendiente($itemId));
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_combined_incidents_account_for_received_and_missing_units_separately(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 7, 1, 1, 1, true)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertSame(9, (int) $linea->cantidad_recibida);
        $this->assertSame(7, (int) $linea->cantidad_aceptada);
        $this->assertSame(1, (int) $linea->cantidad_danada);
        $this->assertSame(1, (int) $linea->cantidad_incorrecta);
        $this->assertSame(1, (int) $linea->cantidad_faltante_declarada);
        $this->assertSame(7, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(7, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_all_damaged_units_create_incidence_without_inventory_entry(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 2, 0, 0, 'S');

        $recepcion = app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 0, 2, 0, 0, true)],
            (string) Str::uuid()
        );

        $linea = $recepcion->items->first();
        $this->assertSame(2, (int) $linea->cantidad_recibida);
        $this->assertSame(0, (int) $linea->cantidad_aceptada);
        $this->assertSame(2, (int) $linea->cantidad_danada);
        $this->assertSame(0, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_purchase_with_closed_incidence_does_not_accept_a_new_normal_receipt(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 2, 0, 0, 'S');
        $service = app(CompraRecepcionService::class);

        $service->registrarRecepcion($compra, [
            $this->linea($itemId, 0, 2, 0, 0, true),
        ], (string) Str::uuid());

        $this->assertReceptionRejected(fn () => $service->registrarRecepcion(
            $compra->fresh(),
            [$this->linea($itemId, 1, 0, 0, 0, true)],
            (string) Str::uuid()
        ));

        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('CON_INCIDENCIAS', $compra->fresh()->estado_recepcion);
    }

    public function test_over_receipt_is_rejected_without_persisting_anything(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 11, 0, 0, 0, true)],
            (string) Str::uuid()
        ));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('EN_TRANSITO', $compra->fresh()->estado_recepcion);
    }

    public function test_closing_an_item_with_pending_units_is_rejected(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 4, 0, 0, 0, true)],
            (string) Str::uuid()
        ));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('EN_TRANSITO', $compra->fresh()->estado_recepcion);
    }

    public function test_item_from_another_purchase_is_rejected(): void
    {
        $compra = $this->createPurchase();
        $otraCompra = $this->createPurchase();
        [, $itemId] = $this->createItem($otraCompra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 1)],
            (string) Str::uuid()
        ));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame('EN_TRANSITO', $compra->fresh()->estado_recepcion);
    }

    public function test_repeated_item_in_the_same_payload_is_rejected(): void
    {
        $compra = $this->createPurchase();
        [, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');

        $this->assertReceptionRejected(fn () => app(CompraRecepcionService::class)->registrarRecepcion(
            $compra,
            [$this->linea($itemId, 2), $this->linea($itemId, 3)],
            (string) Str::uuid()
        ));

        $this->assertSame(0, CompraRecepcion::query()->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->count());
    }

    public function test_same_idempotency_key_for_partial_receipt_does_not_duplicate_stock(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 0, 0, 'S');
        $service = app(CompraRecepcionService::class);
        $key = (string) Str::uuid();
        $payload = [$this->linea($itemId, 4)];

        $first = $service->registrarRecepcion($compra, $payload, $key);
        $second = $service->registrarRecepcion($compra->fresh(), $payload, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CompraRecepcion::query()->count());
        $this->assertSame(1, CompraRecepcionItem::query()->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(4, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
    }

    public function test_global_state_waits_for_all_variants_before_becoming_received(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemS] = $this->createItem($compra, 5, 0, 0, 'S');
        [, $itemM] = $this->createItem($compra, 5, 0, 0, 'M', $productoId);
        $service = app(CompraRecepcionService::class);

        $service->registrarRecepcion($compra, [
            $this->linea($itemS, 5, 0, 0, 0, true),
            $this->linea($itemM, 2),
        ], (string) Str::uuid());

        $this->assertSame('RECEPCION_PARCIAL', $compra->fresh()->estado_recepcion);

        $service->registrarRecepcion($compra->fresh(), [
            $this->linea($itemM, 3, 0, 0, 0, true),
        ], (string) Str::uuid());

        $this->assertSame('RECIBIDA', $compra->fresh()->estado_recepcion);
        $this->assertSame(5, (int) DB::table('colores_productos')->where('producto_id', $productoId)->where('talla_por_color', 'S')->value('stock_por_color'));
        $this->assertSame(5, (int) DB::table('colores_productos')->where('producto_id', $productoId)->where('talla_por_color', 'M')->value('stock_por_color'));
        $this->assertSame(3, DB::table('inventario_movimientos')->count());
    }

    public function test_historical_incidence_is_preserved_when_other_items_arrive_cleanly(): void
    {
        $compra = $this->createPurchase();
        [$productoId, $itemA] = $this->createItem($compra, 1, 0, 0, 'S');
        [, $itemB] = $this->createItem($compra, 1, 0, 0, 'M', $productoId);
        $service = app(CompraRecepcionService::class);

        $service->registrarRecepcion($compra, [
            $this->linea($itemA, 0, 1, 0, 0, true),
        ], (string) Str::uuid());
        $this->assertSame('RECEPCION_PARCIAL', $compra->fresh()->estado_recepcion);

        $service->registrarRecepcion($compra->fresh(), [
            $this->linea($itemB, 1, 0, 0, 0, true),
        ], (string) Str::uuid());

        $this->assertSame('CON_INCIDENCIAS', $compra->fresh()->estado_recepcion);
        $this->assertSame(1, (int) DB::table('colores_productos')->where('producto_id', $productoId)->where('talla_por_color', 'M')->value('stock_por_color'));
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
    }

    private function linea(
        int $itemId,
        int $aceptada,
        int $danada = 0,
        int $incorrecta = 0,
        int $faltante = 0,
        bool $cierra = false
    ): array {
        return [
            'compra_item_id' => $itemId,
            'cantidad_aceptada' => $aceptada,
            'cantidad_danada' => $danada,
            'cantidad_incorrecta' => $incorrecta,
            'cantidad_faltante_declarada' => $faltante,
            'cierra_item' => $cierra,
        ];
    }

    private function pendiente(int $itemId): int
    {
        $item = DB::table('compra_items')->where('id', $itemId)->first();
        $contabilizado = DB::table('compra_recepcion_items')
            ->where('compra_item_id', $itemId)
            ->selectRaw('COALESCE(SUM(cantidad_recibida), 0) + COALESCE(SUM(cantidad_faltante_declarada), 0) AS total')
            ->value('total');

        return (int) $item->cantidad - (int) $contabilizado;
    }

    private function assertReceptionRejected(callable $callback): void
    {
        try {
            $callback();
        } catch (CompraRecepcionException $exception) {
            $this->assertNotSame('', $exception->getMessage());
            return;
        }

        $this->fail('Se esperaba una CompraRecepcionException.');
    }

    private function createPurchase(?string $estadoRecepcion = 'EN_TRANSITO', string $estadoCompra = '1'): Compra
    {
        $id = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra de recepción',
            'estado_compra' => $estadoCompra,
            'estado_recepcion' => $estadoRecepcion,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Compra::query()->findOrFail($id);
    }

    private function createItem(
        Compra $compra,
        int $cantidad,
        int $stockIngresado,
        int $stockFisico,
        string $talla,
        ?int $productoId = null
    ): array {
        if ($productoId === null) {
            $productoId = DB::table('productos')->insertGetId([
                'stock_venta_producto' => $stockFisico,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $colorId = 1;
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla_por_color' => $talla,
            'stock_por_color' => $stockFisico,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compra->id,
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla' => $talla,
            'cantidad' => $cantidad,
            'stock_ingresado' => $stockIngresado,
            'costo_unitario_pesos' => 10,
            'costo_unitario_dolares' => 10,
            'subtotal_pesos' => $cantidad * 10,
            'subtotal_dolares' => $cantidad * 10,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$productoId, $itemId];
    }

    private function runReceptionMigrations(): void
    {
        foreach (glob(database_path('migrations/2026_10_01_1717*.php')) as $path) {
            $migration = require $path;
            $migration->up();
        }
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->string('estado_compra')->default('1');
            $table->string('estado_recepcion')->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_venta_producto')->default(0);
            $table->boolean('publicado_ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla', 50)->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('stock_ingresado')->default(0);
            $table->decimal('costo_unitario_pesos', 12, 2)->default(0);
            $table->decimal('costo_unitario_dolares', 12, 2)->default(0);
            $table->decimal('subtotal_pesos', 12, 2)->default(0);
            $table->decimal('subtotal_dolares', 12, 2)->default(0);
            $table->string('estado', 30)->default('ACTIVO');
            $table->text('observacion')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tipo_movimiento', 40);
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion', 80);
            $table->string('entidad', 120)->nullable();
            $table->string('entidad_id', 120)->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
