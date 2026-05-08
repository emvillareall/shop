<?php

namespace Tests\Feature;

use App\Livewire\Ecommerce\Checkout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutFlowFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useSqliteInMemory();
        $this->createSchema();
    }

    private function useSqliteInMemory(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    private function createSchema(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
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

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto')->nullable();
            $table->string('descripcion_producto')->nullable();
            $table->integer('cantidad_compra_producto')->default(0);
            $table->integer('stock_venta_producto')->default(0);
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->integer('estado_producto')->default(1);
            $table->integer('compras_id')->nullable();
            $table->integer('categoria_producto_id')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_pedido')->nullable();
            $table->unsignedBigInteger('clientes_id');
            $table->unsignedBigInteger('tienda_id')->nullable();
            $table->string('descripcion')->nullable();
            $table->decimal('subtotal_pedido', 12, 2)->default(0);
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

        Schema::create('detalle_pedidos', function (Blueprint $table) {
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

        Schema::create('pagos', function (Blueprint $table) {
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

        Schema::create('pagos_transferencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pago_id');
            $table->string('numero_referencia')->nullable();
            $table->dateTime('fecha_transferencia')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table) {
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

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion');
            $table->string('entidad')->nullable();
            $table->string('entidad_id')->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_checkout_transferencia_creates_pending_review_order_and_confirmation(): void
    {
        DB::table('productos')->insert([
            'id' => 1,
            'descripcion_producto' => 'Body Test',
            'stock_venta_producto' => 5,
            'precio_venta_producto' => 25.50,
            'estado_producto' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('colores_productos')->insert([
            'id' => 1,
            'producto_id' => 1,
            'colores_id' => 2,
            'talla_por_color' => 'M',
            'cantidad_por_color' => 5,
            'stock_por_color' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->put('carrito', [[
            'producto_id' => 1,
            'descripcion' => 'Body Test',
            'color' => 'Negro',
            'color_id' => 2,
            'talla' => 'M',
            'cantidad' => 2,
            'precio' => 25.50,
        ]]);

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

        $pedido = DB::table('pedidos')->first();
        $this->assertNotNull($pedido);
        $this->assertSame('PAGO_EN_REVISION', $pedido->estado_pedido);
        $this->assertSame('EN_REVISION', $pedido->estado_pago);

        $pago = DB::table('pagos')->where('pedido_id', $pedido->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame('transferencia', $pago->metodo);
        $this->assertSame('EN_REVISION', $pago->estado);

        $this->assertDatabaseCount('detalle_pedidos', 1);
        $this->assertSame(3, (int) DB::table('colores_productos')->where('id', 1)->value('stock_por_color'));
        $this->assertSame(3, (int) DB::table('productos')->where('id', 1)->value('stock_venta_producto'));

        $response = $this->get(route('ecommerce.pedido.confirmado', $pedido->id));
        $response->assertOk();
        $response->assertSee('Pedido registrado');
        $response->assertSee('TRANSFERENCIA');
    }
}
