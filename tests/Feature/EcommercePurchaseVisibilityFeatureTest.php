<?php

namespace Tests\Feature;

use App\Livewire\Ecommerce\CarritoCompras;
use App\Livewire\Ecommerce\Checkout;
use App\Models\Producto;
use App\Services\Inventario\StockReservaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EcommercePurchaseVisibilityFeatureTest extends TestCase
{
    private int $colorId;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('security.storefront_open', true);
        Config::set('payments.fake_mode', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();

        $this->colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_direct_add_rejects_unpublished_and_inactive_products_without_reservation(): void
    {
        $hidden = $this->createProduct([
            'descripcion_producto' => 'Oculto con stock',
            'stock_venta_producto' => 10,
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
        ]);
        $inactive = $this->createProduct([
            'descripcion_producto' => 'Inactivo publicado',
            'stock_venta_producto' => 10,
            'estado_producto' => 0,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($hidden, 10);
        $this->addVariant($inactive, 10);

        foreach ([$hidden, $inactive] as $producto) {
            $this->from('/shop')->post(route('ecommerce.carrito.agregar'), [
                'producto_id' => $producto->id,
                'color_id' => $this->colorId,
                'talla' => 'M',
                'cantidad' => 1,
            ])->assertRedirect('/shop')
                ->assertSessionHas('error', 'Este producto ya no está disponible en la tienda online.');
        }

        $this->assertSame([], session()->get('carrito', []));
        $this->assertDatabaseCount('stock_reservas', 0);
        $this->assertSame(10, (int) DB::table('colores_productos')->value('stock_por_color'));
    }

    public function test_published_product_can_be_added_and_creates_only_its_session_reservation(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'estado_producto' => 1,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($producto, 5);

        $this->from('/shop')->post(route('ecommerce.carrito.agregar'), [
            'producto_id' => $producto->id,
            'color_id' => $this->colorId,
            'talla' => 'M',
            'cantidad' => 2,
        ])->assertRedirect('/shop')
            ->assertSessionHas('success', 'Producto agregado al carrito.');

        $this->assertCount(1, session()->get('carrito', []));
        $this->assertDatabaseHas('stock_reservas', [
            'session_id' => session()->getId(),
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);
        $this->assertSame(5, (int) DB::table('colores_productos')->value('stock_por_color'));
    }

    public function test_cart_revalidation_removes_hidden_item_and_releases_only_current_session_reservation(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'publicado_ecommerce' => false,
        ]);
        $this->addVariant($producto, 5);

        $currentSession = session()->getId();
        $otherSession = 'other-session-id';
        session()->put('carrito', [$this->cartItem($producto)]);
        DB::table('stock_reservas')->insert([
            [
                'session_id' => $currentSession,
                'producto_id' => $producto->id,
                'color_id' => $this->colorId,
                'talla' => 'M',
                'cantidad' => 1,
                'expires_at' => now()->addMinutes(15),
                'metadata' => json_encode(['source' => 'shop_cart']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => $otherSession,
                'producto_id' => $producto->id,
                'color_id' => $this->colorId,
                'talla' => 'M',
                'cantidad' => 1,
                'expires_at' => now()->addMinutes(15),
                'metadata' => json_encode(['source' => 'shop_cart']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Livewire::test(CarritoCompras::class)
            ->assertSet('items', [])
            ->assertHasErrors(['carrito']);

        $this->assertSame([], session()->get('carrito', []));
        $this->assertDatabaseMissing('stock_reservas', ['session_id' => $currentSession]);
        $this->assertDatabaseHas('stock_reservas', [
            'session_id' => $otherSession,
            'producto_id' => $producto->id,
        ]);

        DB::table('productos')->where('id', $producto->id)->update(['publicado_ecommerce' => true]);
        Livewire::test(CarritoCompras::class)->assertSet('items', []);
    }

    public function test_hidden_manipulated_session_is_rejected_by_checkout_without_order_or_stock_change(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'publicado_ecommerce' => false,
        ]);
        $this->addVariant($producto, 5);
        session()->put('carrito', [$this->cartItem($producto)]);

        Livewire::test(Checkout::class)
            ->call('confirmarPedido')
            ->assertHasErrors(['cliente_id']);

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame(5, (int) DB::table('colores_productos')->value('stock_por_color'));
        $this->assertSame(5, (int) DB::table('productos')->value('stock_venta_producto'));
        $this->assertDatabaseCount('stock_reservas', 0);
    }

    public function test_checkout_rejects_after_product_is_hidden_and_does_not_create_order(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'estado_producto' => 1,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($producto, 5);
        session()->put('carrito', [$this->cartItem($producto)]);

        $checkout = Livewire::test(Checkout::class);
        $this->assertDatabaseHas('stock_reservas', [
            'session_id' => session()->getId(),
            'producto_id' => $producto->id,
        ]);

        DB::table('productos')->where('id', $producto->id)->update(['publicado_ecommerce' => false]);

        $checkout->call('confirmarPedido')->assertHasErrors();

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame(5, (int) DB::table('colores_productos')->value('stock_por_color'));
        $this->assertSame(5, (int) DB::table('productos')->value('stock_venta_producto'));
    }

    public function test_published_checkout_happy_path_still_creates_order_and_consumes_stock(): void
    {
        Storage::fake('public');
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'precio_venta_producto' => 25.50,
            'estado_producto' => 1,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($producto, 5);
        session()->put('carrito', [$this->cartItem($producto, 2)]);

        Livewire::test(Checkout::class)
            ->set('cedula', '0922222222')
            ->set('cliente.nombres_clientes', 'Ana')
            ->set('cliente.apellidos_clientes', 'Perez')
            ->set('cliente.telefono_clientes', '0999999999')
            ->set('cliente.ciudad_clientes', 'Guayaquil')
            ->set('cliente.direccion_clientes', 'Centro')
            ->set('cliente.email_clientes', 'ana@example.com')
            ->set('metodo_pago', 'transferencia')
            ->set('referencia_transferencia', 'TRX-001')
            ->set('comprobante_transferencia', UploadedFile::fake()->image('voucher.jpg'))
            ->call('confirmarPedido')
            ->assertRedirect();

        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertSame(3, (int) DB::table('colores_productos')->value('stock_por_color'));
        $this->assertSame(3, (int) DB::table('productos')->value('stock_venta_producto'));
        $this->assertDatabaseCount('stock_reservas', 0);
    }

    public function test_final_visibility_guard_rejects_hidden_product_even_with_valid_reservation(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 5,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($producto, 5);
        $item = $this->cartItem($producto);
        app(StockReservaService::class)->syncFromCart(session()->getId(), [$item]);

        DB::table('productos')->where('id', $producto->id)->update(['publicado_ecommerce' => false]);

        $this->expectException(\RuntimeException::class);
        DB::transaction(function () use ($item): void {
            app(StockReservaService::class)->assertVisibleEcommerceCart([$item], true);
        });
    }

    public function test_published_stock_zero_remains_additionally_blocked_by_stock_not_visibility(): void
    {
        $producto = $this->createProduct([
            'stock_venta_producto' => 0,
            'estado_producto' => 1,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($producto, 0);

        $this->from('/shop')->post(route('ecommerce.carrito.agregar'), [
            'producto_id' => $producto->id,
            'color_id' => $this->colorId,
            'talla' => 'M',
            'cantidad' => 1,
        ])->assertRedirect('/shop')
            ->assertSessionHas('error', 'La variante seleccionada está agotada.');

        $this->assertDatabaseCount('stock_reservas', 0);
    }

    private function createProduct(array $overrides = []): Producto
    {
        $published = array_key_exists('publicado_ecommerce', $overrides)
            ? (bool) $overrides['publicado_ecommerce']
            : false;
        unset($overrides['publicado_ecommerce']);

        $producto = new Producto();
        $producto->fill(array_merge([
            'codigo_producto' => 'BUY-' . uniqid(),
            'descripcion_producto' => 'Producto de compra',
            'cantidad_compra_producto' => 1,
            'stock_venta_producto' => 0,
            'precio_pesos_producto' => 100,
            'precio_dolares_producto' => 25,
            'precio_venta_producto' => 100,
            'promocion_activa' => false,
            'estado_producto' => 1,
        ], $overrides));
        $producto->publicado_ecommerce = $published;
        $producto->save();

        return $producto->fresh();
    }

    private function addVariant(Producto $producto, int $stock): int
    {
        return DB::table('colores_productos')->insertGetId([
            'producto_id' => $producto->id,
            'colores_id' => $this->colorId,
            'talla_por_color' => 'M',
            'cantidad_por_color' => $stock,
            'stock_por_color' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function cartItem(Producto $producto, int $cantidad = 1): array
    {
        return [
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion_producto,
            'color' => 'Negro',
            'color_id' => $this->colorId,
            'talla' => 'M',
            'cantidad' => $cantidad,
            'precio' => (float) $producto->precio_venta_producto,
        ];
    }

    private function createSchema(): void
    {
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_producto')->nullable();
            $table->string('descripcion_producto')->nullable();
            $table->integer('cantidad_compra_producto')->default(0);
            $table->integer('stock_venta_producto')->default(0);
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->decimal('precio_promocional', 12, 2)->nullable();
            $table->boolean('promocion_activa')->default(false);
            $table->dateTime('promocion_fecha_inicio')->nullable();
            $table->dateTime('promocion_fecha_fin')->nullable();
            $table->string('promocion_etiqueta')->nullable();
            $table->integer('estado_producto')->default(1);
            $table->boolean('publicado_ecommerce')->default(false);
            $table->integer('compras_id')->nullable();
            $table->integer('categoria_producto_id')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->timestamps();
        });

        Schema::create('colores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_color');
            $table->string('codigo_color')->nullable();
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_reservas', function (Blueprint $table): void {
            $table->id();
            $table->string('session_id', 120)->index();
            $table->unsignedBigInteger('producto_id')->index();
            $table->unsignedBigInteger('color_id')->index();
            $table->string('talla', 40)->index();
            $table->unsignedInteger('cantidad');
            $table->timestamp('expires_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'producto_id', 'color_id', 'talla']);
        });

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('cedula_clientes')->nullable();
            $table->string('nombres_clientes')->nullable();
            $table->string('apellidos_clientes')->nullable();
            $table->string('telefono_clientes')->nullable();
            $table->string('ciudad_clientes')->nullable();
            $table->string('direccion_clientes')->nullable();
            $table->string('email_clientes')->nullable();
            $table->integer('estado_clientes')->default(1);
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_pedido')->nullable();
            $table->unsignedBigInteger('clientes_id')->nullable();
            $table->unsignedBigInteger('tienda_id')->nullable();
            $table->string('descripcion')->nullable();
            $table->decimal('subtotal_pedido', 12, 2)->default(0);
            $table->decimal('iva_pedido', 12, 2)->default(0);
            $table->decimal('descuentos_pedido', 12, 2)->default(0);
            $table->decimal('total_pedido', 12, 2)->default(0);
            $table->string('estado_url')->nullable();
            $table->integer('estado_pedidos')->default(1);
            $table->string('estado_pedido')->nullable();
            $table->string('estado_pago')->nullable();
            $table->string('estado_envio')->nullable();
            $table->dateTime('confirmado_at')->nullable();
            $table->dateTime('pagado_at')->nullable();
            $table->dateTime('despachado_at')->nullable();
            $table->dateTime('cancelado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_pedidos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad_producto')->default(0);
            $table->unsignedBigInteger('id_color_producto')->nullable();
            $table->string('talla_por_color')->nullable();
            $table->string('nombre_producto_snapshot')->nullable();
            $table->string('color_snapshot')->nullable();
            $table->string('talla_snapshot')->nullable();
            $table->decimal('precio_unitario_snapshot', 12, 2)->default(0);
            $table->decimal('subtotal_linea', 12, 2)->default(0);
            $table->decimal('descuento_linea', 12, 2)->default(0);
            $table->decimal('impuesto_linea', 12, 2)->default(0);
            $table->integer('estado_dtpedidos')->default(1);
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->string('metodo');
            $table->string('estado');
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('moneda')->nullable();
            $table->string('referencia_externa')->nullable();
            $table->string('comprobante_path')->nullable();
            $table->text('observacion')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->dateTime('revisado_at')->nullable();
            $table->dateTime('aprobado_at')->nullable();
            $table->dateTime('rechazado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pagos_transferencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pago_id');
            $table->string('banco_origen')->nullable();
            $table->string('titular_origen')->nullable();
            $table->string('numero_referencia')->nullable();
            $table->dateTime('fecha_transferencia')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tipo_movimiento');
            $table->integer('cantidad')->default(0);
            $table->integer('stock_antes')->default(0);
            $table->integer('stock_despues')->default(0);
            $table->string('motivo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('parametros', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_parametro')->unique();
            $table->decimal('valor_parametro', 12, 4)->default(0);
            $table->string('estado_parametro')->default('1');
            $table->timestamps();
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 30)->unique();
            $table->string('environment', 20)->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->string('base_url')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id', 120)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('webhook_id', 180)->nullable();
            $table->string('webhook_secret', 200)->nullable();
            $table->string('verify_path', 120)->nullable();
            $table->boolean('strict_webhook')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion');
            $table->string('entidad');
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }
}
