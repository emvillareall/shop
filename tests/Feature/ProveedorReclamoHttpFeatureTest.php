<?php

namespace Tests\Feature;

use App\Models\ProveedorReclamo;
use App\Models\ProveedorReclamoItem;
use App\Models\ProveedorReclamoResolucion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProveedorReclamoHttpFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createBaseSchema();
        $this->runClaimMigrations();
    }

    public function test_claim_routes_require_authentication_and_full_admin_access(): void
    {
        $fixture = $this->createFixture(['cantidad_danada' => 1]);
        $claim = $this->createClaim($fixture);
        $scopedUser = $this->createUser(false);

        $this->app['auth']->logout();
        $this->get(route('compras.reclamos.show', [$fixture['compra_id'], $claim->id]))
            ->assertRedirect(route('login'));

        $this->actingAs($scopedUser)
            ->get(route('compras.reclamos.show', [$fixture['compra_id'], $claim->id]))
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_send_and_finalize_commercial_claim_without_stock_change(): void
    {
        $fixture = $this->createFixture(['cantidad_danada' => 2]);
        $admin = $this->createUser(true);
        $beforeStock = DB::table('colores_productos')->where('id', $fixture['variante_id'])->value('stock_por_color');
        $beforeMovements = DB::table('inventario_movimientos')->count();

        $response = $this->actingAs($admin)->post(route('compras.reclamos.store', $fixture['compra_id']), [
            'lineas' => [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => 'DANADA',
                'cantidad_reclamada' => 2,
                'observacion' => 'Daño documentado.',
            ]],
        ]);
        $claim = ProveedorReclamo::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('compras.reclamos.show', [$fixture['compra_id'], $claim]));
        $this->actingAs($admin)
            ->get(route('compras.reclamos.show', [$fixture['compra_id'], $claim]))
            ->assertOk()
            ->assertSee('Enviar reclamo');

        $this->actingAs($admin)->post(route('compras.reclamos.enviar', [$fixture['compra_id'], $claim]))
            ->assertRedirect();

        $line = $claim->items()->firstOrFail();
        $this->actingAs($admin)->post(route('compras.reclamos.resoluciones.store', [$fixture['compra_id'], $claim, $line]), [
            'tipo_resolucion' => ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            'cantidad' => 2,
            'referencia' => 'NC-HTTP-1',
            'importe' => '12.50',
            'moneda' => 'USD',
        ])->assertRedirect();

        $resolution = $line->resoluciones()->firstOrFail();
        $this->actingAs($admin)->post(route('compras.reclamos.resoluciones.resolver', [$fixture['compra_id'], $claim, $resolution]))
            ->assertRedirect();

        $this->assertSame(ProveedorReclamoResolucion::ESTADO_RESUELTA, $resolution->fresh()->estado);
        $this->assertSame(ProveedorReclamoItem::ESTADO_RESUELTO, $line->fresh()->estado);
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $claim->fresh()->estado);
        $this->assertSame($beforeStock, DB::table('colores_productos')->where('id', $fixture['variante_id'])->value('stock_por_color'));
        $this->assertSame($beforeMovements, DB::table('inventario_movimientos')->count());
    }

    public function test_authorized_user_can_receive_reposition_http_and_retry_idempotently(): void
    {
        $fixture = $this->createFixture(['cantidad_danada' => 1]);
        $admin = $this->createUser(true);
        $claim = $this->createClaim($fixture);

        $this->actingAs($admin)->post(route('compras.reclamos.enviar', [$fixture['compra_id'], $claim]));
        $line = $claim->items()->firstOrFail();
        $this->actingAs($admin)->post(route('compras.reclamos.resoluciones.store', [$fixture['compra_id'], $claim, $line]), [
            'tipo_resolucion' => ProveedorReclamoResolucion::TIPO_REPOSICION,
            'cantidad' => 1,
        ]);
        $resolution = $line->resoluciones()->firstOrFail();

        $beforeReception = (array) DB::table('compra_recepcion_items')->where('id', $fixture['recepcion_item_id'])->first();
        $beforeStock = (int) DB::table('colores_productos')->where('id', $fixture['variante_id'])->value('stock_por_color');
        $key = (string) Str::uuid();
        $payload = [
            'idempotency_key' => $key,
            'cantidad_aceptada' => 1,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'observacion' => 'Llegada física revisada.',
        ];

        $this->actingAs($admin)->post(route('compras.reclamos.reposiciones.store', [$fixture['compra_id'], $claim, $resolution]), $payload)
            ->assertRedirect();
        $this->actingAs($admin)->post(route('compras.reclamos.reposiciones.store', [$fixture['compra_id'], $claim, $resolution]), $payload)
            ->assertRedirect();

        $this->assertDatabaseCount('proveedor_reclamo_reposiciones', 1);
        $this->assertDatabaseCount('inventario_movimientos', 1);
        $this->assertDatabaseHas('inventario_movimientos', [
            'tipo_movimiento' => 'ENTRADA_REPOSICION_PROVEEDOR',
            'cantidad' => 1,
            'proveedor_reclamo_reposicion_id' => 1,
        ]);
        $this->assertSame($beforeStock + 1, (int) DB::table('colores_productos')->where('id', $fixture['variante_id'])->value('stock_por_color'));
        $this->assertSame($beforeReception, (array) DB::table('compra_recepcion_items')->where('id', $fixture['recepcion_item_id'])->first());
        $this->assertSame('CON_INCIDENCIAS', DB::table('compras')->where('id', $fixture['compra_id'])->value('estado_recepcion'));
        $this->assertSame(ProveedorReclamo::ESTADO_RESUELTO, $claim->fresh()->estado);
    }

    public function test_purchase_reclams_screen_reports_no_pending_incidents_when_source_is_clean(): void
    {
        $fixture = $this->createFixture();
        $summary = app(\App\Services\Compras\ProveedorReclamoService::class)
            ->resumenParaCompra(\App\Models\Compra::findOrFail($fixture['compra_id']));

        $this->assertCount(0, $summary['incidencias_reclamables']);
    }

    private function createClaim(array $fixture): ProveedorReclamo
    {
        $admin = $this->createUser(true);
        $this->actingAs($admin)->post(route('compras.reclamos.store', $fixture['compra_id']), [
            'lineas' => [[
                'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
                'tipo_incidencia' => 'DANADA',
                'cantidad_reclamada' => (int) ($fixture['cantidad_danada'] ?? 1),
            ]],
        ])->assertRedirect();

        return ProveedorReclamo::query()->latest('id')->firstOrFail();
    }

    private function createUser(bool $admin): \App\Models\User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $admin ? 'Claims Admin' : 'Claims Scoped',
            'email' => uniqid('claims-http-').'@example.test',
            'password' => 'password',
            'estado_user' => '1',
            'acceso_administrativo_completo' => $admin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \App\Models\User::findOrFail($id);
    }

    private function createFixture(array $overrides = []): array
    {
        $user = $this->createUser(true);
        $providerId = DB::table('proveedores')->insertGetId(['tienda_proveedor' => 'Proveedor HTTP', 'created_at' => now(), 'updated_at' => now()]);
        $compraId = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-HTTP-'.uniqid(),
            'descripcion_compra' => 'Compra HTTP',
            'proveedor_id' => $providerId,
            'estado_compra' => '1',
            'estado_recepcion' => 'CON_INCIDENCIAS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productoId = DB::table('productos')->insertGetId(['stock_venta_producto' => 10, 'created_at' => now(), 'updated_at' => now()]);
        $varianteId = DB::table('colores_productos')->insertGetId([
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla_por_color' => 'M',
            'stock_por_color' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraItemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla' => 'M',
            'cantidad' => 2,
            'stock_ingresado' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recepcionId = DB::table('compra_recepciones')->insertGetId([
            'compra_id' => $compraId,
            'user_id' => $user->id,
            'fecha_recepcion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $values = array_merge([
            'compra_recepcion_id' => $recepcionId,
            'compra_item_id' => $compraItemId,
            'cantidad_recibida' => 2,
            'cantidad_aceptada' => 0,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'cantidad_faltante_declarada' => 0,
            'cierra_item' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
        $recepcionItemId = DB::table('compra_recepcion_items')->insertGetId($values);

        return compact('compraId', 'productoId', 'varianteId', 'compraItemId', 'recepcionId', 'recepcionItemId') + [
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'variante_id' => $varianteId,
            'recepcion_item_id' => $recepcionItemId,
            'cantidad_danada' => (int) $values['cantidad_danada'],
        ];
    }

    private function runClaimMigrations(): void
    {
        $paths = glob(database_path('migrations/2026_10_05_16000*.php')) ?: [];
        sort($paths);
        foreach ($paths as $path) {
            $migration = require $path;
            $migration->up();
        }
        $migration = require database_path('migrations/2026_10_06_100000_create_proveedor_reclamo_reposiciones_table.php');
        $migration->up();
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password');
            $table->string('estado_user')->default('1'); $table->boolean('acceso_administrativo_completo')->default(false); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('accion', 80);
            $table->string('entidad', 120)->nullable(); $table->string('entidad_id', 120)->nullable();
            $table->json('valores_antes')->nullable(); $table->json('valores_despues')->nullable();
            $table->string('ip', 64)->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
        Schema::create('proveedores', function (Blueprint $table): void { $table->id(); $table->string('tienda_proveedor'); $table->timestamps(); });
        Schema::create('compras', function (Blueprint $table): void { $table->id(); $table->string('codigo_compra'); $table->string('descripcion_compra'); $table->foreignId('proveedor_id')->constrained('proveedores'); $table->string('estado_compra')->default('1'); $table->string('estado_recepcion')->default('EN_TRANSITO'); $table->timestamps(); });
        Schema::create('productos', function (Blueprint $table): void { $table->id(); $table->string('descripcion_producto')->nullable(); $table->integer('stock_venta_producto')->default(0); $table->timestamps(); });
        Schema::create('colores', function (Blueprint $table): void { $table->id(); $table->string('nombre_color')->nullable(); $table->timestamps(); });
        Schema::create('colores_productos', function (Blueprint $table): void { $table->id(); $table->foreignId('producto_id')->constrained('productos'); $table->unsignedBigInteger('colores_id'); $table->string('talla_por_color'); $table->integer('stock_por_color')->default(0); $table->timestamps(); });
        DB::table('colores')->insert(['id' => 1, 'nombre_color' => 'Azul', 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('compra_items', function (Blueprint $table): void { $table->id(); $table->foreignId('compra_id')->constrained('compras'); $table->unsignedBigInteger('producto_id')->nullable(); $table->unsignedBigInteger('colores_id')->nullable(); $table->string('talla')->nullable(); $table->integer('cantidad')->default(0); $table->integer('stock_ingresado')->default(0); $table->timestamps(); });
        Schema::create('compra_recepciones', function (Blueprint $table): void { $table->id(); $table->foreignId('compra_id')->constrained('compras'); $table->foreignId('user_id')->nullable()->constrained('users'); $table->dateTime('fecha_recepcion'); $table->timestamps(); });
        Schema::create('compra_recepcion_items', function (Blueprint $table): void { $table->id(); $table->foreignId('compra_recepcion_id')->constrained('compra_recepciones'); $table->foreignId('compra_item_id')->constrained('compra_items'); $table->unsignedInteger('cantidad_recibida')->default(0); $table->unsignedInteger('cantidad_aceptada')->default(0); $table->unsignedInteger('cantidad_danada')->default(0); $table->unsignedInteger('cantidad_incorrecta')->default(0); $table->unsignedInteger('cantidad_faltante_declarada')->default(0); $table->boolean('cierra_item')->default(false); $table->timestamps(); });
        Schema::create('inventario_movimientos', function (Blueprint $table): void { $table->id(); $table->foreignId('producto_id')->constrained('productos'); $table->unsignedBigInteger('colores_productos_id')->nullable(); $table->unsignedBigInteger('pedido_id')->nullable(); $table->unsignedBigInteger('compra_id')->nullable(); $table->foreignId('user_id')->nullable()->constrained('users'); $table->string('tipo_movimiento', 60); $table->integer('cantidad'); $table->integer('stock_antes')->nullable(); $table->integer('stock_despues')->nullable(); $table->unsignedBigInteger('compra_item_id')->nullable(); $table->unsignedBigInteger('compra_recepcion_id')->nullable(); $table->unsignedBigInteger('compra_recepcion_item_id')->nullable(); $table->string('motivo', 180)->nullable(); $table->json('metadata')->nullable(); $table->timestamps(); });
    }
}
