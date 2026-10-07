<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompraRecepcionHttpFeatureTest extends TestCase
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

    public function test_admin_can_open_reception_form_with_pending_summary_and_server_key(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        $this->createItem($compra, 10, 'S');

        $response = $this->actingAs($user)->get(route('compras.recepcion.create', $compra));

        $response->assertOk()
            ->assertSee('Recepción de compra #'.$compra->id)
            ->assertSee('Producto de prueba')
            ->assertSee('Pendiente')
            ->assertSee('Confirmar recepción')
            ->assertSee('Recibir todo correctamente');

        preg_match('/name="idempotency_key" value="([^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertTrue((bool) preg_match('/^[0-9a-f-]{36}$/i', $matches[1]));
    }

    public function test_non_admin_cannot_open_reception_form(): void
    {
        $user = $this->createUser(['acceso_administrativo_completo' => false]);
        $compra = $this->createPurchase();
        $this->createItem($compra, 10, 'S');

        $this->actingAs($user)
            ->get(route('compras.recepcion.create', $compra))
            ->assertForbidden();
    }

    public function test_ineligible_states_do_not_show_a_normal_reception_form(): void
    {
        $user = $this->createUser();

        foreach (['RECIBIDA', 'CON_INCIDENCIAS', null] as $estado) {
            $compra = $this->createPurchase($estado);
            $this->createItem($compra, 10, 'S');

            $this->actingAs($user)
                ->get(route('compras.recepcion.create', $compra))
                ->assertRedirect(route('compras.show', $compra));
        }

        $anulada = $this->createPurchase('EN_TRANSITO', 'ANULADA');
        $this->createItem($anulada, 10, 'S');

        $this->actingAs($user)
            ->get(route('compras.recepcion.create', $anulada))
            ->assertRedirect(route('compras.show', $anulada));
    }

    public function test_manual_partial_receipt_updates_stock_and_status(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)
            ->post(route('compras.recepciones.store', $compra), [
                'modo' => 'manual',
                'idempotency_key' => (string) Str::uuid(),
                'items' => [$itemId => $this->line($itemId, 4)],
            ])
            ->assertRedirect(route('compras.show', $compra))
            ->assertSessionHas('success', 'Recepción registrada correctamente.');

        $this->assertSame('RECEPCION_PARCIAL', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(4, (int) DB::table('compra_items')->where('id', $itemId)->value('stock_ingresado'));
        $this->assertSame(4, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(1, DB::table('compra_recepciones')->count());
        $this->assertSame(4, (int) DB::table('compra_recepcion_items')->value('cantidad_aceptada'));
    }

    public function test_manual_complete_receipt_reaches_received_state(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)->post(route('compras.recepciones.store', $compra), [
            'modo' => 'manual',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [$itemId => $this->line($itemId, 10, 0, 0, 0, true)],
        ])->assertRedirect(route('compras.show', $compra));

        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(1, DB::table('inventario_movimientos')->where('tipo_movimiento', 'ENTRADA_COMPRA')->count());
    }

    public function test_receive_all_correctly_uses_the_complete_service_path(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [$productoId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)->post(route('compras.recepciones.store', $compra), [
            'modo' => 'todo',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('compras.show', $compra));

        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(10, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
    }

    public function test_manual_incident_and_missing_receipts_are_recorded_without_overstating_stock(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)->post(route('compras.recepciones.store', $compra), [
            'modo' => 'manual',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [$itemId => $this->line($itemId, 9, 0, 0, 1, true)],
        ])->assertRedirect(route('compras.show', $compra));

        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(9, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(9, (int) DB::table('compra_recepcion_items')->value('cantidad_recibida'));
        $this->assertSame(1, (int) DB::table('compra_recepcion_items')->value('cantidad_faltante_declarada'));
    }

    public function test_negative_http_quantity_is_rejected_before_calling_the_service(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [, $itemId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)->from(route('compras.recepcion.create', $compra))
            ->post(route('compras.recepciones.store', $compra), [
                'modo' => 'manual',
                'idempotency_key' => (string) Str::uuid(),
                'items' => [$itemId => $this->line($itemId, -1)],
            ])
            ->assertRedirect(route('compras.recepcion.create', $compra))
            ->assertSessionHasErrors('items.'.$itemId.'.cantidad_aceptada');

        $this->assertSame(0, DB::table('compra_recepciones')->count());
    }

    public function test_duplicate_post_with_same_key_does_not_duplicate_reception_or_stock(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [$productoId, $itemId] = $this->createItem($compra, 10, 'S');
        $key = (string) Str::uuid();
        $payload = [
            'modo' => 'manual',
            'idempotency_key' => $key,
            'items' => [$itemId => $this->line($itemId, 4)],
        ];

        $this->actingAs($user)->post(route('compras.recepciones.store', $compra), $payload)->assertRedirect();
        $this->actingAs($user)->post(route('compras.recepciones.store', $compra->fresh()), $payload)->assertRedirect();

        $this->assertSame(1, DB::table('compra_recepciones')->count());
        $this->assertSame(1, DB::table('compra_recepcion_items')->count());
        $this->assertSame(1, DB::table('inventario_movimientos')->count());
        $this->assertSame(4, (int) DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
    }

    public function test_purchase_show_displays_reception_history_after_multiple_receipts(): void
    {
        $user = $this->createUser();
        $compra = $this->createPurchase();
        [, $itemId] = $this->createItem($compra, 10, 'S');

        $this->actingAs($user)->post(route('compras.recepciones.store', $compra), [
            'modo' => 'manual',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [$itemId => $this->line($itemId, 4)],
        ]);
        $this->actingAs($user)->post(route('compras.recepciones.store', $compra->fresh()), [
            'modo' => 'manual',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [$itemId => $this->line($itemId, 6, 0, 0, 0, true)],
        ]);

        $this->actingAs($user)
            ->get(route('compras.show', $compra))
            ->assertOk()
            ->assertSee('Historial de recepciones')
            ->assertSee('Recibido físicamente')
            ->assertSee('RECIBIDA');

        $this->assertSame(2, DB::table('compra_recepciones')->count());
    }

    private function line(
        int $itemId,
        int $accepted,
        int $damaged = 0,
        int $incorrect = 0,
        int $missing = 0,
        bool $close = false
    ): array {
        return [
            'compra_item_id' => $itemId,
            'cantidad_aceptada' => $accepted,
            'cantidad_danada' => $damaged,
            'cantidad_incorrecta' => $incorrect,
            'cantidad_faltante_declarada' => $missing,
            'cierra_item' => $close ? '1' : '0',
        ];
    }

    private function createUser(array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Reception Admin',
            'email' => 'reception-admin-'.uniqid().'@example.test',
            'password' => Hash::make('test-password'),
            'estado_user' => '1',
            'acceso_administrativo_completo' => true,
            'puede_ver_productos' => true,
            'puede_crear_productos' => true,
            'puede_editar_productos' => true,
        ], $overrides));
        $user->save();

        return $user->refresh();
    }

    private function createPurchase(?string $estadoRecepcion = 'EN_TRANSITO', string $estadoCompra = '1'): Compra
    {
        $proveedorId = DB::table('proveedores')->insertGetId([
            'tienda_proveedor' => 'Proveedor de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $id = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra HTTP de prueba',
            'envio_compra' => 0,
            'importacion_compra' => 0,
            'total_pesos_compra' => 100,
            'total_dolares_compra' => 100,
            'total_final_compra' => 100,
            'estado_compra' => $estadoCompra,
            'estado_recepcion' => $estadoRecepcion,
            'proveedor_id' => $proveedorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Compra::query()->findOrFail($id);
    }

    private function createItem(Compra $compra, int $cantidad, string $talla): array
    {
        $productoId = DB::table('productos')->insertGetId([
            'codigo_producto' => 'PRODUCTO-'.uniqid(),
            'descripcion_producto' => 'Producto de prueba',
            'cantidad_compra_producto' => $cantidad,
            'stock_venta_producto' => 0,
            'precio_pesos_producto' => 10,
            'precio_dolares_producto' => 10,
            'precio_venta_producto' => 20,
            'compras_id' => $compra->id,
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
            'cantidad_por_color' => $cantidad,
            'stock_por_color' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compra->id,
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla' => $talla,
            'cantidad' => $cantidad,
            'stock_ingresado' => 0,
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

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto')->nullable();
            $table->string('descripcion_producto')->nullable();
            $table->integer('cantidad_compra_producto')->default(0);
            $table->integer('stock_venta_producto')->default(0);
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->unsignedBigInteger('compras_id')->nullable();
            $table->string('estado_producto')->default('1');
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
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
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
