<?php

namespace Tests\Feature;

use App\Http\Controllers\CompraController;
use App\Models\Compra;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\User;
use App\Services\Compras\CompraRecepcionException;
use App\Services\Compras\CompraRecepcionService;
use App\Services\Inventario\InventarioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryPurchaseFlowFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_new_purchase_is_in_transit_without_stock_entry(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $compraId = $this->createPurchase();
        $colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => 'NEGRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/productos', [
                'codigo_producto' => 'LEGGING-X',
                'descripcion_producto' => 'Legging X',
                'cantidad_compra_producto' => 10,
                'stock_venta_producto' => 10,
                'precio_pesos_producto' => 10,
                'precio_dolares_producto' => 10,
                'precio_venta_producto' => 20,
                'compras_id' => $compraId,
                'categoria_producto_id' => 1,
                'estado_producto' => '1',
                'imagen' => UploadedFile::fake()->image('legging.jpg', 120, 120),
                'colores_id' => [$colorId],
                'talla_por_color' => ['S'],
                'cantidad_por_color' => [10],
                'stock_por_color' => [10],
            ])
            ->assertRedirect();

        $producto = Producto::query()->where('codigo_producto', 'LEGGING-X')->firstOrFail();
        $variante = DB::table('colores_productos')->where('producto_id', $producto->id)->first();
        $compraItem = DB::table('compra_items')->where('compra_id', $compraId)->first();

        $this->assertSame('EN_TRANSITO', (string) DB::table('compras')->where('id', $compraId)->value('estado_recepcion'));
        $this->assertFalse((bool) $producto->fresh()->publicado_ecommerce);
        $this->assertSame(0, (int) $variante->stock_por_color);
        $this->assertSame(0, (int) $producto->fresh()->stock_venta_producto);
        $this->assertSame(10, (int) $compraItem->cantidad);
        $this->assertSame(0, (int) $compraItem->stock_ingresado);
        $this->assertSame(10.0, (float) $compraItem->costo_unitario_pesos);
        $this->assertSame(100.0, (float) $compraItem->subtotal_pesos);
        $this->assertSame(100.0, (float) DB::table('compras')->where('id', $compraId)->value('total_pesos_compra'));
        $this->assertSame(100.0, (float) DB::table('compras')->where('id', $compraId)->value('total_dolares_compra'));
        $this->assertSame(100.0, (float) DB::table('compras')->where('id', $compraId)->value('total_final_compra'));
        $this->assertSame(0, InventarioMovimiento::query()
            ->where('producto_id', $producto->id)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->count());
        $this->assertSame(0, (int) InventarioMovimiento::query()
            ->where('producto_id', $producto->id)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->sum('cantidad'));
    }

    public function test_multiple_new_variants_remain_out_of_stock_in_transit(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $compraId = $this->createPurchase();
        $colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => 'NEGRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/productos', [
                'codigo_producto' => 'LEGGING-MULTI',
                'descripcion_producto' => 'Legging multi',
                'cantidad_compra_producto' => 8,
                'stock_venta_producto' => 8,
                'precio_pesos_producto' => 10,
                'precio_dolares_producto' => 10,
                'precio_venta_producto' => 20,
                'compras_id' => $compraId,
                'categoria_producto_id' => 1,
                'estado_producto' => '1',
                'imagen' => UploadedFile::fake()->image('legging-multi.jpg', 120, 120),
                'colores_id' => [$colorId, $colorId],
                'talla_por_color' => ['S', 'M'],
                'cantidad_por_color' => [5, 3],
                'stock_por_color' => [5, 3],
            ])
            ->assertRedirect();

        $producto = Producto::query()->where('codigo_producto', 'LEGGING-MULTI')->firstOrFail();
        $items = DB::table('compra_items')
            ->where('compra_id', $compraId)
            ->orderBy('talla')
            ->get(['talla', 'cantidad', 'stock_ingresado']);
        $stocks = DB::table('colores_productos')
            ->where('producto_id', $producto->id)
            ->orderBy('talla_por_color')
            ->pluck('stock_por_color', 'talla_por_color')
            ->map(fn ($stock) => (int) $stock)
            ->all();

        $this->assertSame([
            ['talla' => 'M', 'cantidad' => 3, 'stock_ingresado' => 0],
            ['talla' => 'S', 'cantidad' => 5, 'stock_ingresado' => 0],
        ], $items->map(fn ($item) => [
            'talla' => $item->talla,
            'cantidad' => (int) $item->cantidad,
            'stock_ingresado' => (int) $item->stock_ingresado,
        ])->all());
        $this->assertSame(['M' => 0, 'S' => 0], $stocks);
        $this->assertSame(0, (int) $producto->fresh()->stock_venta_producto);
        $this->assertSame(0, InventarioMovimiento::query()
            ->where('compra_id', $compraId)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->count());
    }

    public function test_new_purchase_header_is_marked_in_transit(): void
    {
        $proveedorId = DB::table('proveedores')->insertGetId([
            'tienda_proveedor' => 'Proveedor de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(CompraController::class)->store(Request::create('/compras', 'POST', [
            'codigo_compra' => 'COMPRA-CONTROLLER-'.uniqid(),
            'descripcion_compra' => 'Compra en transito',
            'envio_compra' => 0,
            'importacion_compra' => 0,
            'proveedor_id' => $proveedorId,
            'estado_compra' => '1',
        ]));

        $this->assertSame('EN_TRANSITO', (string) Compra::query()->latest('id')->value('estado_recepcion'));
    }

    public function test_transit_purchase_without_reception_is_annulled_without_inventory_changes(): void
    {
        $user = $this->createUser();
        $compraId = $this->createPurchase();
        $itemId = $this->createPurchaseItem($compraId);

        $this->actingAs($user)
            ->post(route('compras.anular', $compraId))
            ->assertRedirect()
            ->assertSessionHas('success', 'Compra anulada correctamente.');

        $this->assertSame('ANULADA', DB::table('compras')->where('id', $compraId)->value('estado_compra'));
        $this->assertSame('EN_TRANSITO', DB::table('compras')->where('id', $compraId)->value('estado_recepcion'));
        $this->assertSame('ACTIVO', DB::table('compra_items')->where('id', $itemId)->value('estado'));
        $this->assertSame(0, DB::table('compra_recepciones')->where('compra_id', $compraId)->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->where('compra_id', $compraId)->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->where('tipo_movimiento', 'REVERSO_COMPRA')->count());
    }

    public function test_partial_reception_blocks_simple_cancellation(): void
    {
        $this->assertCancellationBlockedForReceivedState('RECEPCION_PARCIAL');
    }

    public function test_received_purchase_blocks_simple_cancellation(): void
    {
        $this->assertCancellationBlockedForReceivedState('RECIBIDA');
    }

    public function test_incident_purchase_blocks_simple_cancellation_even_without_accepted_stock(): void
    {
        $this->assertCancellationBlockedForReceivedState('CON_INCIDENCIAS', [
            'cantidad_recibida' => 2,
            'cantidad_aceptada' => 0,
            'cantidad_danada' => 2,
            'cierra_item' => true,
        ]);
    }

    public function test_existing_reception_blocks_cancellation_even_if_state_says_in_transit(): void
    {
        $this->assertCancellationBlockedForReceivedState('EN_TRANSITO');
    }

    public function test_normalized_historical_purchase_is_blocked_from_cancellation(): void
    {
        $this->assertCancellationBlockedForReceivedState('RECIBIDA', [
            'observacion' => 'Recepción histórica normalizada; no generó movimiento de inventario.',
            'user_id' => null,
        ]);
    }

    public function test_anulled_purchase_cannot_be_received_afterwards(): void
    {
        $user = $this->createUser();
        $compraId = $this->createPurchase();
        $itemId = $this->createPurchaseItem($compraId);

        $this->actingAs($user)
            ->post(route('compras.anular', $compraId))
            ->assertRedirect();

        try {
            app(CompraRecepcionService::class)->recibirCompleta(
                $compraId,
                (string) Str::uuid(),
                $user->id
            );
            $this->fail('Se esperaba el rechazo de recepción para una compra anulada.');
        } catch (CompraRecepcionException $exception) {
            $this->assertSame('No se puede recibir una compra anulada.', $exception->getMessage());
        }

        $this->assertSame('ANULADA', DB::table('compras')->where('id', $compraId)->value('estado_compra'));
        $this->assertSame(0, DB::table('compra_recepciones')->where('compra_id', $compraId)->count());
        $this->assertSame(0, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(0, DB::table('inventario_movimientos')->where('compra_id', $compraId)->count());
    }

    public function test_cancellation_button_is_visible_only_for_transit_without_reception(): void
    {
        $user = $this->createUser();
        $transitId = $this->createPurchase();
        $this->createPurchaseWithStatusAndReception('RECEPCION_PARCIAL');
        $this->createPurchaseWithStatusAndReception('RECIBIDA');
        $this->createPurchaseWithStatusAndReception('CON_INCIDENCIAS', [
            'cantidad_recibida' => 2,
            'cantidad_aceptada' => 0,
            'cantidad_danada' => 2,
            'cierra_item' => true,
        ]);
        $anulledId = $this->createPurchase();
        DB::table('compras')->where('id', $anulledId)->update(['estado_compra' => 'ANULADA']);

        $response = $this->actingAs($user)->get(route('compras.index'));

        $response->assertSee('data-testid="anular-compra-'.$transitId.'"', false);
        foreach (DB::table('compras')->whereIn('estado_recepcion', [
            'RECEPCION_PARCIAL',
            'RECIBIDA',
            'CON_INCIDENCIAS',
        ])->pluck('id') as $compraId) {
            $response->assertDontSee('data-testid="anular-compra-'.$compraId.'"', false);
        }
        $response->assertDontSee('data-testid="anular-compra-'.$anulledId.'"', false);
    }

    public function test_transit_purchase_anulation_has_no_inventory_entry_to_revert(): void
    {
        $compraId = $this->createPurchase();
        $productoId = DB::table('productos')->insertGetId([
            'codigo_producto' => 'PRODUCTO-ANULACION',
            'descripcion_producto' => 'Producto en transito',
            'cantidad_compra_producto' => 10,
            'stock_venta_producto' => 0,
            'precio_pesos_producto' => 10,
            'precio_dolares_producto' => 10,
            'precio_venta_producto' => 20,
            'compras_id' => $compraId,
            'estado_producto' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => 'NEGRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla_por_color' => 'S',
            'cantidad_por_color' => 10,
            'stock_por_color' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla' => 'S',
            'cantidad' => 10,
            'stock_ingresado' => 0,
            'costo_unitario_pesos' => 10,
            'costo_unitario_dolares' => 10,
            'subtotal_pesos' => 100,
            'subtotal_dolares' => 100,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, app(InventarioService::class)->revertirCompra($compraId));
        $this->assertSame(0, DB::table('inventario_movimientos')->where('compra_id', $compraId)->count());
        $this->assertSame(0, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame('ACTIVO', DB::table('compra_items')->where('id', $itemId)->value('estado'));
    }

    public function test_existing_variant_entry_preserves_previous_stock(): void
    {
        [$productoId, $colorId] = $this->createProductWithVariant(2, 'S');

        app(InventarioService::class)->registrarEntradaVariante(
            $productoId,
            $colorId,
            'S',
            10,
            $this->createPurchase(),
            null,
            'Prueba de entrada adicional'
        );

        $this->assertSame(12, (int) DB::table('colores_productos')->value('stock_por_color'));
        $this->assertSame(12, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame(1, InventarioMovimiento::query()
            ->where('producto_id', $productoId)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->count());
        $this->assertSame(10, (int) InventarioMovimiento::query()
            ->where('producto_id', $productoId)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->sum('cantidad'));
    }

    public function test_multiple_variant_entries_sync_product_total_once(): void
    {
        [$productoId, $colorId] = $this->createProductWithVariant(0, 'S');
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla_por_color' => 'M',
            'cantidad_por_color' => 3,
            'stock_por_color' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InventarioService::class);
        $compraId = $this->createPurchase();
        $service->registrarEntradaVariante($productoId, $colorId, 'S', 5, $compraId);
        $service->registrarEntradaVariante($productoId, $colorId, 'M', 3, $compraId);

        $stocks = DB::table('colores_productos')
            ->where('producto_id', $productoId)
            ->orderBy('talla_por_color')
            ->pluck('stock_por_color', 'talla_por_color')
            ->map(fn ($stock) => (int) $stock)
            ->all();

        $this->assertSame(['M' => 3, 'S' => 5], $stocks);
        $this->assertSame(8, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame(2, InventarioMovimiento::query()
            ->where('producto_id', $productoId)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->count());
        $this->assertSame(8, (int) InventarioMovimiento::query()
            ->where('producto_id', $productoId)
            ->where('tipo_movimiento', 'ENTRADA_COMPRA')
            ->sum('cantidad'));
    }

    private function createUser(): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Inventory Test User',
            'email' => 'inventory-test@example.test',
            'password' => Hash::make('test-password'),
            'estado_user' => '1',
            'acceso_administrativo_completo' => true,
            'puede_ver_productos' => true,
            'puede_crear_productos' => true,
            'puede_editar_productos' => true,
        ]);
        $user->save();

        return $user->refresh();
    }

    private function createPurchase(): int
    {
        return DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra de prueba',
            'envio_compra' => 0,
            'importacion_compra' => 0,
            'total_pesos_compra' => 0,
            'total_dolares_compra' => 0,
            'total_final_compra' => 0,
            'estado_compra' => '1',
            'estado_recepcion' => 'EN_TRANSITO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPurchaseItem(int $compraId, int $cantidad = 10, int $stockIngresado = 0): int
    {
        return DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'cantidad' => $cantidad,
            'stock_ingresado' => $stockIngresado,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPurchaseWithStatusAndReception(string $estado, array $reception = []): int
    {
        $compraId = $this->createPurchase();
        $itemId = $this->createPurchaseItem($compraId, (int) ($reception['cantidad'] ?? 2));
        $defaults = [
            'cantidad_recibida' => 1,
            'cantidad_aceptada' => 1,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'cantidad_faltante_declarada' => 0,
            'cierra_item' => false,
            'observacion' => null,
            'user_id' => 1,
        ];
        $reception = array_merge($defaults, $reception);
        $recepcionId = DB::table('compra_recepciones')->insertGetId([
            'compra_id' => $compraId,
            'user_id' => $reception['user_id'],
            'fecha_recepcion' => now(),
            'observacion' => $reception['observacion'],
            'idempotency_key' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('compra_recepcion_items')->insert([
            'compra_recepcion_id' => $recepcionId,
            'compra_item_id' => $itemId,
            'cantidad_recibida' => $reception['cantidad_recibida'],
            'cantidad_aceptada' => $reception['cantidad_aceptada'],
            'cantidad_danada' => $reception['cantidad_danada'],
            'cantidad_incorrecta' => $reception['cantidad_incorrecta'],
            'cantidad_faltante_declarada' => $reception['cantidad_faltante_declarada'],
            'cierra_item' => $reception['cierra_item'],
            'observacion' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('compras')->where('id', $compraId)->update(['estado_recepcion' => $estado]);

        return $compraId;
    }

    private function assertCancellationBlockedForReceivedState(string $estado, array $reception = []): void
    {
        $user = $this->createUser();
        $compraId = $this->createPurchaseWithStatusAndReception($estado, $reception);

        $response = $this->actingAs($user)
            ->post(route('compras.anular', $compraId));

        $response->assertRedirect()
            ->assertSessionHas('danger', 'No se puede anular esta compra porque ya tiene una recepción registrada o no está en tránsito.');
        $this->assertSame($estado, DB::table('compras')->where('id', $compraId)->value('estado_recepcion'));
        $this->assertSame('1', (string) DB::table('compras')->where('id', $compraId)->value('estado_compra'));
        $this->assertSame(1, DB::table('compra_recepciones')->where('compra_id', $compraId)->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->where('compra_id', $compraId)->count());
        $this->assertSame(0, DB::table('inventario_movimientos')->where('tipo_movimiento', 'REVERSO_COMPRA')->count());
    }

    private function createProductWithVariant(int $stock, string $talla): array
    {
        $compraId = $this->createPurchase();
        $productoId = DB::table('productos')->insertGetId([
            'codigo_producto' => 'PRODUCTO-'.uniqid(),
            'descripcion_producto' => 'Producto de prueba',
            'cantidad_compra_producto' => $stock,
            'stock_venta_producto' => $stock,
            'precio_pesos_producto' => 10,
            'precio_dolares_producto' => 10,
            'precio_venta_producto' => 20,
            'compras_id' => $compraId,
            'estado_producto' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => 'NEGRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla_por_color' => $talla,
            'cantidad_por_color' => $stock,
            'stock_por_color' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$productoId, $colorId];
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('estado_user')->default('1');
            $table->boolean('acceso_administrativo_completo')->default(false);
            $table->boolean('puede_ver_productos')->default(false);
            $table->boolean('puede_crear_productos')->default(false);
            $table->boolean('puede_editar_productos')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('tienda_proveedor');
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->decimal('envio_compra', 12, 2)->default(0);
            $table->decimal('importacion_compra', 12, 2)->default(0);
            $table->decimal('total_pesos_compra', 12, 2)->default(0);
            $table->decimal('total_dolares_compra', 12, 2)->default(0);
            $table->decimal('total_final_compra', 12, 2)->default(0);
            $table->string('estado_compra')->default('1');
            $table->string('estado_recepcion')->nullable();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->timestamps();
        });

        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_parametro')->unique();
            $table->decimal('valor_parametro', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto');
            $table->string('descripcion_producto');
            $table->string('cantidad_compra_producto');
            $table->string('stock_venta_producto')->default('0');
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->decimal('precio_promocional', 12, 2)->nullable();
            $table->boolean('promocion_activa')->default(false);
            $table->dateTime('promocion_fecha_inicio')->nullable();
            $table->dateTime('promocion_fecha_fin')->nullable();
            $table->string('promocion_etiqueta')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->unsignedBigInteger('compras_id');
            $table->unsignedBigInteger('categoria_producto_id')->nullable();
            $table->string('estado_producto')->default('1');
            $table->boolean('publicado_ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('colores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_color');
            $table->string('codigo_color')->nullable();
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla_por_color')->nullable();
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('ruta')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('color_nombre')->nullable();
            $table->string('color_normalizado')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('peso_original')->nullable();
            $table->unsignedBigInteger('peso_optimizado')->nullable();
            $table->unsignedInteger('ancho')->nullable();
            $table->unsignedInteger('alto')->nullable();
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

        Schema::create('compra_recepciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('fecha_recepcion');
            $table->text('observacion')->nullable();
            $table->char('idempotency_key', 36)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('compra_recepcion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_recepcion_id');
            $table->unsignedBigInteger('compra_item_id');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_aceptada')->default(0);
            $table->unsignedInteger('cantidad_danada')->default(0);
            $table->unsignedInteger('cantidad_incorrecta')->default(0);
            $table->unsignedInteger('cantidad_faltante_declarada')->default(0);
            $table->boolean('cierra_item')->default(false);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->unique(['compra_recepcion_id', 'compra_item_id']);
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
