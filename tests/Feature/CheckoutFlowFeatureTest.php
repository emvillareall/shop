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
            $table->boolean('publicado_ecommerce')->default(false);
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

        Schema::create('pagos_payphone', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pago_id');
            $table->string('payphone_status')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->unique();
            $table->string('environment')->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->string('base_url')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('webhook_id')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('verify_path')->nullable();
            $table->boolean('strict_webhook')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_parametro');
            $table->float('valor_parametro')->default(0);
            $table->string('estado_parametro')->default('1');
            $table->timestamps();
        });

        Schema::create('stock_reservas', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 120);
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('color_id');
            $table->string('talla', 40);
            $table->unsignedInteger('cantidad');
            $table->dateTime('expires_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'producto_id', 'color_id', 'talla']);
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
            'publicado_ecommerce' => 1,
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
            ->set('cedula', ' 1712345675 ')
            ->set('cliente.nombres_clientes', ' Ana   María ')
            ->set('cliente.apellidos_clientes', ' Pérez   López ')
            ->set('cliente.telefono_clientes', ' +593 9 872-14365 ')
            ->set('cliente.ciudad_clientes', ' Guayaquil  ')
            ->set('cliente.direccion_clientes', ' Av. 9 de Octubre y Rumichaca, sector centro ')
            ->set('cliente.email_clientes', ' ANA.Ejemplo@Example.COM ')
            ->set('metodo_pago', 'transferencia')
            ->set('referencia_transferencia', 'TRX-001')
            ->set('comprobante_transferencia', UploadedFile::fake()->image('voucher.jpg'))
            ->call('confirmarPedido')
            ->assertRedirect();

        $pedido = DB::table('pedidos')->first();
        $this->assertNotNull($pedido);
        $this->assertDatabaseHas('clientes', [
            'id' => $pedido->clientes_id,
            'cedula_clientes' => '1712345675',
            'nombres_clientes' => 'Ana María',
            'apellidos_clientes' => 'Pérez López',
            'telefono_clientes' => '0987214365',
            'ciudad_clientes' => 'Guayaquil',
            'direccion_clientes' => 'Av. 9 de Octubre y Rumichaca, sector centro',
            'email_clientes' => 'ana.ejemplo@example.com',
        ]);
        $this->assertSame('PAGO_EN_REVISION', $pedido->estado_pedido);
        $this->assertSame('EN_REVISION', $pedido->estado_pago);
        $this->assertSame(51.00, (float) $pedido->subtotal_pedido);
        $this->assertSame(0.00, (float) $pedido->iva_pedido);
        $this->assertSame(51.00, (float) $pedido->total_pedido);

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

    public function test_payphone_checkout_persists_the_amount_including_its_service_fee(): void
    {
        Config::set('payments.payphone.enabled', true);
        Config::set('payments.payphone.token', str_repeat('test-token-', 10));
        Config::set('payments.payphone.store_id', '2508e157-f64b-4fb8-9019-5dd032b245b0');
        Config::set('payments.payphone.base_url', 'https://payphone.test/api');
        Config::set('payments.fake_mode', false);

        DB::table('parametros')->insert([
            ['nombre_parametro' => 'iva_general', 'valor_parametro' => 0, 'estado_parametro' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_parametro' => 'recargo_payphone', 'valor_parametro' => 7, 'estado_parametro' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('productos')->insert([
            'id' => 2,
            'descripcion_producto' => 'Artículo de checkout',
            'stock_venta_producto' => 2,
            'precio_venta_producto' => 0.50,
            'estado_producto' => 1,
            'publicado_ecommerce' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('colores_productos')->insert([
            'id' => 2,
            'producto_id' => 2,
            'colores_id' => 2,
            'talla_por_color' => 'M',
            'cantidad_por_color' => 2,
            'stock_por_color' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->put('carrito', [[
            'producto_id' => 2,
            'descripcion' => 'Artículo de checkout',
            'color' => 'Negro',
            'color_id' => 2,
            'talla' => 'M',
            'cantidad' => 1,
            'precio' => 0.50,
        ]]);

        Livewire::test(Checkout::class)
            ->set('cedula', '1712345675')
            ->set('cliente.nombres_clientes', 'Ana')
            ->set('cliente.apellidos_clientes', 'Pérez')
            ->set('cliente.telefono_clientes', '0987214365')
            ->set('cliente.ciudad_clientes', 'Guayaquil')
            ->set('cliente.direccion_clientes', 'Av. Amazonas y Naciones Unidas, sector norte')
            ->set('cliente.email_clientes', 'ana@example.com')
            ->set('metodo_pago', 'payphone')
            ->call('confirmarPedido')
            ->assertRedirect();

        $pedido = DB::table('pedidos')->first();
        $this->assertNotNull($pedido);
        $this->assertSame(0.50, (float) $pedido->subtotal_pedido);
        $this->assertSame(0.00, (float) $pedido->iva_pedido);
        $this->assertSame(0.54, (float) $pedido->total_pedido);

        $pago = DB::table('pagos')->where('pedido_id', $pedido->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame(0.54, (float) $pago->monto);
        $this->assertSame(1, (int) DB::table('colores_productos')->where('id', 2)->value('stock_por_color'));
        $this->assertSame(1, (int) DB::table('productos')->where('id', 2)->value('stock_venta_producto'));
        $this->assertDatabaseHas('inventario_movimientos', [
            'pedido_id' => $pedido->id,
            'tipo_movimiento' => 'SALIDA_VENTA',
            'cantidad' => -1,
        ]);
    }

    public function test_checkout_rejects_invalid_customer_data_before_persisting_a_client_or_order(): void
    {
        DB::table('productos')->insert([
            'id' => 3,
            'descripcion_producto' => 'Producto de validación',
            'stock_venta_producto' => 1,
            'precio_venta_producto' => 10,
            'estado_producto' => 1,
            'publicado_ecommerce' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'id' => 3,
            'producto_id' => 3,
            'colores_id' => 2,
            'talla_por_color' => 'M',
            'cantidad_por_color' => 1,
            'stock_por_color' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        session()->put('carrito', [[
            'producto_id' => 3,
            'descripcion' => 'Producto de validación',
            'color' => 'Negro',
            'color_id' => 2,
            'talla' => 'M',
            'cantidad' => 1,
            'precio' => 10,
        ]]);

        Livewire::test(Checkout::class)
            ->set('cedula', '1234567890')
            ->set('cliente.nombres_clientes', '12345')
            ->set('cliente.apellidos_clientes', '***')
            ->set('cliente.telefono_clientes', '098-111-1111')
            ->set('cliente.ciudad_clientes', '123')
            ->set('cliente.direccion_clientes', 'casa')
            ->set('cliente.email_clientes', 'no-es-correo')
            ->call('confirmarPedido')
            ->assertHasErrors([
                'cedula',
                'cliente.nombres_clientes',
                'cliente.apellidos_clientes',
                'cliente.telefono_clientes',
                'cliente.ciudad_clientes',
                'cliente.direccion_clientes',
                'cliente.email_clientes',
            ]);

        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('pedidos', 0);
    }
}
