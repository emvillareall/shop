<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosHardeningFeatureTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion_producto')->nullable();
            $table->integer('stock_venta_producto')->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->string('imagen_producto')->nullable();
            $table->integer('estado_producto')->default(1);
            $table->boolean('publicado_ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('colores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_color')->nullable();
            $table->string('codigo_color')->nullable();
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id');
            $table->string('cantidad_por_color')->nullable();
            $table->integer('stock_por_color')->default(0);
            $table->string('talla_por_color');
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombres_clientes')->nullable();
            $table->string('apellidos_clientes')->nullable();
            $table->string('cedula_clientes')->nullable();
            $table->string('telefono_clientes')->nullable();
            $table->string('ciudad_clientes')->nullable();
            $table->string('direccion_clientes')->nullable();
            $table->string('email_clientes')->nullable();
            $table->string('estado_clientes')->default('1');
            $table->timestamps();
        });

        Schema::create('tiendas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_tienda');
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_pedido')->nullable();
            $table->unsignedBigInteger('clientes_id');
            $table->unsignedBigInteger('tienda_id');
            $table->string('descripcion')->nullable();
            $table->decimal('subtotal_pedido', 12, 2)->default(0);
            $table->decimal('descuentos_pedido', 12, 2)->default(0);
            $table->decimal('total_pedido', 12, 2)->default(0);
            $table->string('estado_pedidos')->default('1');
            $table->string('estado_url')->nullable();
            $table->string('estado_pedido')->nullable();
            $table->string('estado_pago')->nullable();
            $table->string('estado_envio')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamp('despachado_at')->nullable();
            $table->timestamp('cancelado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_pedidos', function (Blueprint $table): void {
            $table->id();
            $table->integer('cantidad_producto');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('id_color_producto')->nullable();
            $table->string('talla_por_color')->nullable();
            $table->string('nombre_producto_snapshot')->nullable();
            $table->string('color_snapshot')->nullable();
            $table->string('talla_snapshot')->nullable();
            $table->decimal('precio_unitario_snapshot', 12, 2)->nullable();
            $table->decimal('subtotal_linea', 12, 2)->nullable();
            $table->decimal('descuento_linea', 12, 2)->nullable();
            $table->decimal('impuesto_linea', 12, 2)->nullable();
            $table->string('estado_dtpedidos')->default('1');
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->string('metodo');
            $table->string('estado');
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('moneda')->default('USD');
            $table->string('referencia_externa')->nullable();
            $table->text('observacion')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamp('rechazado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('clientes_id')->nullable();
            $table->unsignedBigInteger('tienda_id')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado_venta')->default('BORRADOR');
            $table->string('modalidad_venta')->nullable();
            $table->timestamp('fecha_venta')->nullable();
            $table->timestamp('fecha_proximo_abono')->nullable();
            $table->timestamp('fecha_ultimo_abono')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_movimiento');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->integer('cantidad')->default(0);
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

        DB::table('colores')->insert([
            'id' => 1,
            'nombre_color' => 'Negro',
            'codigo_color' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('clientes')->insert([
            'id' => 1,
            'nombres_clientes' => 'Cliente',
            'apellidos_clientes' => 'Prueba',
            'cedula_clientes' => '0100000000',
            'telefono_clientes' => '0000000000',
            'ciudad_clientes' => 'Quito',
            'direccion_clientes' => 'Direccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tiendas')->insert([
            'id' => 1,
            'nombre_tienda' => 'Tienda principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = (new User())->forceFill([
            'id' => 1,
            'estado_user' => '1',
            'acceso_administrativo_completo' => true,
        ]);
    }

    public function test_inactive_product_direct_post_is_rejected_without_sale_or_stock_change(): void
    {
        [$productoId, $varianteId] = $this->createProduct(0, true, 10);
        $before = $this->countsFor($productoId, $varianteId);

        $this->submitSale($productoId, $varianteId)
            ->assertSessionHasErrors('lineas_json');

        $this->assertSame('El producto ya no está disponible para venta.', session('errors')->first('lineas_json'));
        $this->assertSame($before, $this->countsFor($productoId, $varianteId));
        $this->assertSame(0, DB::table('inventario_movimientos')->where('tipo_movimiento', 'SALIDA_VENTA')->count());
    }

    public function test_active_unpublished_product_remains_sellable_in_pos(): void
    {
        [$productoId, $varianteId] = $this->createProduct(1, false, 10);

        $this->submitSale($productoId, $varianteId)
            ->assertRedirect(route('home'));

        $this->assertSame(9, (int) DB::table('colores_productos')->where('id', $varianteId)->value('stock_por_color'));
        $this->assertSame(9, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertFalse((bool) DB::table('productos')->where('id', $productoId)->value('publicado_ecommerce'));
        $this->assertSame(1, DB::table('pedidos')->count());
        $this->assertSame(1, DB::table('ventas')->count());
        $this->assertSame(1, DB::table('detalle_pedidos')->count());
    }

    public function test_active_published_product_remains_sellable_in_pos(): void
    {
        [$productoId, $varianteId] = $this->createProduct(1, true, 10);

        $this->submitSale($productoId, $varianteId)
            ->assertRedirect(route('home'));

        $this->assertSame(9, (int) DB::table('colores_productos')->where('id', $varianteId)->value('stock_por_color'));
        $this->assertSame(9, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
    }

    public function test_variant_from_another_product_is_rejected_even_with_active_product_id(): void
    {
        [$activeProductId, $activeVariantId] = $this->createProduct(1, false, 10, 'Producto activo');
        [$inactiveProductId, $inactiveVariantId] = $this->createProduct(0, true, 10, 'Producto inactivo');
        $before = $this->countsFor($inactiveProductId, $inactiveVariantId);

        $this->submitSale($activeProductId, $inactiveVariantId)
            ->assertSessionHasErrors('lineas_json');

        $this->assertSame('La variante seleccionada no es válida para esta venta.', session('errors')->first('lineas_json'));
        $this->assertSame($before, $this->countsFor($inactiveProductId, $inactiveVariantId));
        $this->assertSame(0, DB::table('pedidos')->count());
        $this->assertNotSame($activeVariantId, $inactiveVariantId);
    }

    public function test_product_inactivated_before_confirmation_is_rejected(): void
    {
        [$productoId, $varianteId] = $this->createProduct(1, false, 10);
        DB::table('productos')->where('id', $productoId)->update(['estado_producto' => 0]);

        $this->submitSale($productoId, $varianteId)
            ->assertSessionHasErrors('lineas_json');

        $this->assertSame(10, (int) DB::table('colores_productos')->where('id', $varianteId)->value('stock_por_color'));
        $this->assertSame(10, (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'));
        $this->assertSame(0, DB::table('pedidos')->count());
    }

    public function test_insufficient_stock_is_rejected_without_changes(): void
    {
        [$productoId, $varianteId] = $this->createProduct(1, false, 0);

        $this->withoutExceptionHandling();
        try {
            $this->submitSale($productoId, $varianteId);
            $this->fail('La venta debía ser rechazada por stock insuficiente.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Stock insuficiente', $exception->getMessage());
        }

        $this->assertSame(0, (int) DB::table('colores_productos')->where('id', $varianteId)->value('stock_por_color'));
        $this->assertSame(0, DB::table('pedidos')->count());
        $this->assertSame(0, DB::table('ventas')->count());
    }

    private function createProduct(
        int $estado,
        bool $publicado,
        int $stock,
        string $descripcion = 'Producto POS'
    ): array {
        $now = now();
        $productoId = DB::table('productos')->insertGetId([
            'descripcion_producto' => $descripcion,
            'stock_venta_producto' => $stock,
            'precio_venta_producto' => 20,
            'estado_producto' => $estado,
            'publicado_ecommerce' => $publicado,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $varianteId = DB::table('colores_productos')->insertGetId([
            'producto_id' => $productoId,
            'colores_id' => 1,
            'cantidad_por_color' => $stock,
            'stock_por_color' => $stock,
            'talla_por_color' => 'M',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$productoId, $varianteId];
    }

    private function submitSale(int $productoId, int $varianteId, ?int $productoIdInPayload = null)
    {
        return $this->actingAs($this->admin)->post(route('home.venta_fisica'), [
            'descripcion' => 'Prueba POS',
            'tienda_id' => 1,
            'cliente_modo' => 'existente',
            'clientes_id' => 1,
            'lineas_json' => json_encode([[
                'producto_id' => $productoIdInPayload ?? $productoId,
                'variante_id' => $varianteId,
                'colores_id' => 1,
                'talla_por_color' => 'M',
                'cantidad' => 1,
                'producto_nombre' => 'Producto POS',
                'nombre_color' => 'Negro',
            ]]),
            'descuento_extra' => 0,
            'recargo_extra' => 0,
            'metodo_pago_pos' => 'efectivo',
            'modalidad_venta' => 'contado',
        ]);
    }

    private function countsFor(int $productoId, int $varianteId): array
    {
        return [
            'producto_stock' => (int) DB::table('productos')->where('id', $productoId)->value('stock_venta_producto'),
            'variante_stock' => (int) DB::table('colores_productos')->where('id', $varianteId)->value('stock_por_color'),
            'pedidos' => DB::table('pedidos')->count(),
            'ventas' => DB::table('ventas')->count(),
            'detalles' => DB::table('detalle_pedidos')->count(),
            'pagos' => DB::table('pagos')->count(),
        ];
    }
}
