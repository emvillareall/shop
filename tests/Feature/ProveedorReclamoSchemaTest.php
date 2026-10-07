<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Models\ProveedorReclamo;
use App\Models\ProveedorReclamoEvento;
use App\Models\ProveedorReclamoItem;
use App\Models\ProveedorReclamoReposicion;
use App\Models\ProveedorReclamoResolucion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProveedorReclamoSchemaTest extends TestCase
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

    public function test_claim_schema_has_expected_columns_defaults_indexes_and_foreign_keys(): void
    {
        foreach ([
            'proveedor_reclamos',
            'proveedor_reclamo_items',
            'proveedor_reclamo_resoluciones',
            'proveedor_reclamo_eventos',
            'proveedor_reclamo_reposiciones',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $claimColumns = collect(Schema::getColumns('proveedor_reclamos'))->keyBy('name');
        $this->assertFalse($claimColumns['compra_id']['nullable']);
        $this->assertFalse($claimColumns['creado_por']['nullable']);
        $this->assertFalse($claimColumns['estado']['nullable']);
        $this->assertSame('BORRADOR', trim((string) $claimColumns['estado']['default'], "'\""));
        $this->assertTrue($claimColumns['referencia_proveedor']['nullable']);
        $this->assertTrue($claimColumns['observacion']['nullable']);

        $itemColumns = collect(Schema::getColumns('proveedor_reclamo_items'))->keyBy('name');
        $this->assertFalse($itemColumns['proveedor_reclamo_id']['nullable']);
        $this->assertFalse($itemColumns['compra_recepcion_item_id']['nullable']);
        $this->assertFalse($itemColumns['cantidad_reclamada']['nullable']);
        $this->assertSame('PENDIENTE', trim((string) $itemColumns['estado']['default'], "'\""));
        $this->assertTrue($itemColumns['observacion']['nullable']);

        $resolutionColumns = collect(Schema::getColumns('proveedor_reclamo_resoluciones'))->keyBy('name');
        $this->assertFalse($resolutionColumns['proveedor_reclamo_item_id']['nullable']);
        $this->assertFalse($resolutionColumns['cantidad']['nullable']);
        $this->assertTrue($resolutionColumns['resuelto_por']['nullable']);
        $this->assertTrue($resolutionColumns['importe']['nullable']);
        $this->assertTrue($resolutionColumns['moneda']['nullable']);

        $eventColumns = collect(Schema::getColumns('proveedor_reclamo_eventos'))->keyBy('name');
        $this->assertFalse($eventColumns['proveedor_reclamo_id']['nullable']);
        $this->assertTrue($eventColumns['proveedor_reclamo_item_id']['nullable']);
        $this->assertTrue($eventColumns['proveedor_reclamo_resolucion_id']['nullable']);
        $this->assertTrue($eventColumns['usuario_id']['nullable']);
        $this->assertFalse($eventColumns['fecha_evento']['nullable']);

        $reposicionColumns = collect(Schema::getColumns('proveedor_reclamo_reposiciones'))->keyBy('name');
        $this->assertFalse($reposicionColumns['proveedor_reclamo_resolucion_id']['nullable']);
        $this->assertFalse($reposicionColumns['recibido_por']['nullable']);
        $this->assertFalse($reposicionColumns['idempotency_key']['nullable']);
        $this->assertFalse($reposicionColumns['cantidad_recibida']['nullable']);
        $this->assertSame('0', trim((string) $reposicionColumns['cantidad_aceptada']['default'], "'\""));
        $this->assertSame('0', trim((string) $reposicionColumns['cantidad_danada']['default'], "'\""));
        $this->assertSame('0', trim((string) $reposicionColumns['cantidad_incorrecta']['default'], "'\""));
        $this->assertFalse($reposicionColumns['fecha_recepcion']['nullable']);

        $movimientoColumns = collect(Schema::getColumns('inventario_movimientos'))->keyBy('name');
        $this->assertTrue($movimientoColumns['proveedor_reclamo_reposicion_id']['nullable']);

        foreach ([
            ['proveedor_reclamos', 'proveedor_reclamos_compra_id_idx'],
            ['proveedor_reclamos', 'proveedor_reclamos_estado_idx'],
            ['proveedor_reclamo_items', 'proveedor_reclamo_items_reclamo_id_idx'],
            ['proveedor_reclamo_items', 'proveedor_reclamo_items_recepcion_item_id_idx'],
            ['proveedor_reclamo_items', 'proveedor_reclamo_items_tipo_idx'],
            ['proveedor_reclamo_items', 'proveedor_reclamo_items_estado_idx'],
            ['proveedor_reclamo_resoluciones', 'proveedor_reclamo_resoluciones_item_id_idx'],
            ['proveedor_reclamo_resoluciones', 'proveedor_reclamo_resoluciones_tipo_idx'],
            ['proveedor_reclamo_resoluciones', 'proveedor_reclamo_resoluciones_estado_idx'],
            ['proveedor_reclamo_eventos', 'proveedor_reclamo_eventos_reclamo_fecha_idx'],
            ['proveedor_reclamo_reposiciones', 'proveedor_reclamo_reposiciones_idempotency_unique'],
            ['proveedor_reclamo_reposiciones', 'proveedor_reclamo_reposiciones_resolucion_fecha_idx'],
            ['inventario_movimientos', 'inventario_movimientos_reposicion_id_idx'],
        ] as [$table, $index]) {
            $this->assertTrue(Schema::hasIndex($table, $index), $table . ':' . $index);
        }

        $this->assertForeignKey('proveedor_reclamos', 'compra_id', 'compras');
        $this->assertForeignKey('proveedor_reclamos', 'creado_por', 'users');
        $this->assertForeignKey('proveedor_reclamo_items', 'proveedor_reclamo_id', 'proveedor_reclamos');
        $this->assertForeignKey('proveedor_reclamo_items', 'compra_recepcion_item_id', 'compra_recepcion_items');
        $this->assertForeignKey('proveedor_reclamo_resoluciones', 'proveedor_reclamo_item_id', 'proveedor_reclamo_items');
        $this->assertForeignKey('proveedor_reclamo_resoluciones', 'resuelto_por', 'users');
        $this->assertForeignKey('proveedor_reclamo_eventos', 'proveedor_reclamo_id', 'proveedor_reclamos');
        $this->assertForeignKey('proveedor_reclamo_eventos', 'proveedor_reclamo_item_id', 'proveedor_reclamo_items');
        $this->assertForeignKey('proveedor_reclamo_eventos', 'proveedor_reclamo_resolucion_id', 'proveedor_reclamo_resoluciones');
        $this->assertForeignKey('proveedor_reclamo_eventos', 'usuario_id', 'users');
        $this->assertForeignKey(
            'proveedor_reclamo_reposiciones',
            'proveedor_reclamo_resolucion_id',
            'proveedor_reclamo_resoluciones'
        );
        $this->assertForeignKey('proveedor_reclamo_reposiciones', 'recibido_por', 'users');
        $this->assertForeignKey(
            'inventario_movimientos',
            'proveedor_reclamo_reposicion_id',
            'proveedor_reclamo_reposiciones'
        );

        $this->assertDatabaseCount('proveedor_reclamos', 0);
        $this->assertDatabaseCount('proveedor_reclamo_items', 0);
        $this->assertDatabaseCount('proveedor_reclamo_resoluciones', 0);
        $this->assertDatabaseCount('proveedor_reclamo_eventos', 0);
        $this->assertDatabaseCount('proveedor_reclamo_reposiciones', 0);
        $this->assertFalse(Schema::hasColumn('compra_recepciones', 'proveedor_reclamo_id'));
        $this->assertFalse(Schema::hasColumn('compra_recepcion_items', 'cantidad_reclamada'));
    }

    public function test_models_expose_claim_resolution_event_and_source_relations(): void
    {
        $fixture = $this->createReceptionFixture();

        $reclamo = ProveedorReclamo::create([
            'compra_id' => $fixture['compra_id'],
            'creado_por' => $fixture['user_id'],
            'referencia_proveedor' => 'REF-001',
            'observacion' => 'Incidencia de recepción.',
        ]);
        $item = ProveedorReclamoItem::create([
            'proveedor_reclamo_id' => $reclamo->id,
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 2,
        ]);
        $resolucion = ProveedorReclamoResolucion::create([
            'proveedor_reclamo_item_id' => $item->id,
            'tipo_resolucion' => ProveedorReclamoResolucion::TIPO_NOTA_CREDITO,
            'cantidad' => 2,
            'importe' => 12.50,
            'moneda' => 'USD',
            'resuelto_por' => $fixture['user_id'],
            'estado' => ProveedorReclamoResolucion::ESTADO_RESUELTA,
        ]);
        $evento = ProveedorReclamoEvento::create([
            'proveedor_reclamo_id' => $reclamo->id,
            'proveedor_reclamo_item_id' => $item->id,
            'proveedor_reclamo_resolucion_id' => $resolucion->id,
            'accion' => 'RESOLUCION_REGISTRADA',
            'estado_nuevo' => ProveedorReclamo::ESTADO_EN_GESTION,
            'usuario_id' => $fixture['user_id'],
            'fecha_evento' => now(),
        ]);

        $reclamo->load(['compra', 'creadoPor', 'items.resoluciones.resueltoPor', 'eventos.usuario']);
        $item->load(['reclamo', 'compraRecepcionItem', 'resoluciones']);
        $evento->load(['reclamo', 'reclamoItem', 'reclamoResolucion', 'usuario']);

        $this->assertSame($fixture['compra_id'], $reclamo->compra->id);
        $this->assertSame($fixture['provider_id'], $reclamo->compra->proveedor_id);
        $this->assertSame($fixture['user_id'], $reclamo->creadoPor->id);
        $this->assertSame($item->id, $reclamo->items->first()->id);
        $this->assertSame($fixture['recepcion_item_id'], $item->compraRecepcionItem->id);
        $this->assertSame($resolucion->id, $item->resoluciones->first()->id);
        $this->assertSame($fixture['user_id'], $resolucion->resueltoPor->id);
        $this->assertSame($evento->id, $reclamo->eventos->first()->id);
        $this->assertSame($reclamo->id, $evento->reclamo->id);
        $this->assertSame($item->id, $evento->reclamoItem->id);
        $this->assertSame($resolucion->id, $evento->reclamoResolucion->id);
        $this->assertSame($fixture['user_id'], $evento->usuario->id);

        $reposicionResolucion = ProveedorReclamoResolucion::create([
            'proveedor_reclamo_item_id' => $item->id,
            'tipo_resolucion' => ProveedorReclamoResolucion::TIPO_REPOSICION,
            'cantidad' => 2,
        ]);
        $reposicion = ProveedorReclamoReposicion::create([
            'proveedor_reclamo_resolucion_id' => $reposicionResolucion->id,
            'recibido_por' => $fixture['user_id'],
            'idempotency_key' => 'schema-reposicion-000000000000000000000',
            'cantidad_recibida' => 2,
            'cantidad_aceptada' => 1,
            'cantidad_danada' => 1,
            'cantidad_incorrecta' => 0,
            'fecha_recepcion' => now(),
        ]);
        $reposicionResolucion->load('reposiciones');
        $reposicion->load(['resolucion', 'recibidoPor', 'movimientos']);

        $this->assertSame(ProveedorReclamo::ESTADO_BORRADOR, $reclamo->fresh()->estado);
        $this->assertSame('12.50', $resolucion->fresh()->importe);
        $this->assertSame($reposicion->id, $reposicionResolucion->reposiciones->first()->id);
        $this->assertSame($reposicionResolucion->id, $reposicion->resolucion->id);
        $this->assertSame($fixture['user_id'], $reposicion->recibidoPor->id);
        $this->assertCount(0, $reposicion->movimientos);
    }

    public function test_multiple_claim_items_can_reference_same_incident_and_type(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 3]);
        $reclamoA = ProveedorReclamo::create([
            'compra_id' => $fixture['compra_id'],
            'creado_por' => $fixture['user_id'],
        ]);
        $reclamoB = ProveedorReclamo::create([
            'compra_id' => $fixture['compra_id'],
            'creado_por' => $fixture['user_id'],
        ]);

        ProveedorReclamoItem::create([
            'proveedor_reclamo_id' => $reclamoA->id,
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 2,
        ]);
        ProveedorReclamoItem::create([
            'proveedor_reclamo_id' => $reclamoB->id,
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 1,
        ]);

        $this->assertSame(2, DB::table('proveedor_reclamo_items')
            ->where('compra_recepcion_item_id', $fixture['recepcion_item_id'])
            ->where('tipo_incidencia', ProveedorReclamoItem::TIPO_DANADA)
            ->count());
    }

    public function test_claim_models_do_not_change_reception_or_inventory_data(): void
    {
        $fixture = $this->createReceptionFixture(['cantidad_danada' => 1]);
        $beforeReception = DB::table('compra_recepcion_items')
            ->whereKey($fixture['recepcion_item_id'])
            ->first();
        $beforeStock = DB::table('productos')->whereKey($fixture['producto_id'])->value('stock_venta_producto');
        $beforeVariantStock = DB::table('colores_productos')
            ->whereKey($fixture['variante_id'])
            ->value('stock_por_color');
        $beforeMovements = DB::table('inventario_movimientos')->count();

        $reclamo = ProveedorReclamo::create([
            'compra_id' => $fixture['compra_id'],
            'creado_por' => $fixture['user_id'],
        ]);
        ProveedorReclamoItem::create([
            'proveedor_reclamo_id' => $reclamo->id,
            'compra_recepcion_item_id' => $fixture['recepcion_item_id'],
            'tipo_incidencia' => ProveedorReclamoItem::TIPO_DANADA,
            'cantidad_reclamada' => 1,
        ]);

        $afterReception = DB::table('compra_recepcion_items')
            ->whereKey($fixture['recepcion_item_id'])
            ->first();
        $this->assertEquals((array) $beforeReception, (array) $afterReception);
        $this->assertSame($beforeStock, DB::table('productos')->whereKey($fixture['producto_id'])->value('stock_venta_producto'));
        $this->assertSame($beforeVariantStock, DB::table('colores_productos')->whereKey($fixture['variante_id'])->value('stock_por_color'));
        $this->assertSame($beforeMovements, DB::table('inventario_movimientos')->count());
    }

    public function test_down_removes_only_the_new_claim_tables(): void
    {
        $reposicionMigration = require database_path(
            'migrations/2026_10_06_100000_create_proveedor_reclamo_reposiciones_table.php'
        );
        $reposicionMigration->down();
        foreach (array_reverse($this->claimMigrations()) as $migration) {
            $migration->down();
        }

        $this->assertFalse(Schema::hasTable('proveedor_reclamo_eventos'));
        $this->assertFalse(Schema::hasTable('proveedor_reclamo_resoluciones'));
        $this->assertFalse(Schema::hasTable('proveedor_reclamo_items'));
        $this->assertFalse(Schema::hasTable('proveedor_reclamos'));
        $this->assertTrue(Schema::hasTable('compras'));
        $this->assertTrue(Schema::hasTable('compra_recepciones'));
        $this->assertTrue(Schema::hasTable('compra_recepcion_items'));
        $this->assertTrue(Schema::hasTable('inventario_movimientos'));
        $this->assertFalse(Schema::hasTable('proveedor_reclamo_reposiciones'));
        $this->assertFalse(Schema::hasColumn('inventario_movimientos', 'proveedor_reclamo_reposicion_id'));
    }

    private function assertForeignKey(string $table, string $column, string $referencedTable): void
    {
        $foreignKeys = DB::select("PRAGMA foreign_key_list('{$table}')");

        $this->assertTrue(collect($foreignKeys)->contains(
            fn (object $foreignKey): bool => $foreignKey->from === $column
                && $foreignKey->table === $referencedTable,
        ), $table . '.' . $column . ' -> ' . $referencedTable);
    }

    private function createReceptionFixture(array $receptionOverrides = []): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Claims Test User',
            'email' => 'claims-'.uniqid().'@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $providerId = DB::table('proveedores')->insertGetId([
            'tienda_proveedor' => 'Proveedor de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraId = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra de prueba',
            'proveedor_id' => $providerId,
            'estado_compra' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productoId = DB::table('productos')->insertGetId([
            'stock_venta_producto' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $varianteId = DB::table('colores_productos')->insertGetId([
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla_por_color' => 'M',
            'stock_por_color' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraItemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla' => 'M',
            'cantidad' => 3,
            'stock_ingresado' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recepcionId = DB::table('compra_recepciones')->insertGetId([
            'compra_id' => $compraId,
            'user_id' => $userId,
            'fecha_recepcion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recepcionItemId = DB::table('compra_recepcion_items')->insertGetId(array_merge([
            'compra_recepcion_id' => $recepcionId,
            'compra_item_id' => $compraItemId,
            'cantidad_recibida' => 3,
            'cantidad_aceptada' => 3,
            'cantidad_danada' => 0,
            'cantidad_incorrecta' => 0,
            'cantidad_faltante_declarada' => 0,
            'cierra_item' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $receptionOverrides));

        return compact(
            'userId',
            'providerId',
            'compraId',
            'productoId',
            'varianteId',
            'compraItemId',
            'recepcionId',
            'recepcionItemId'
        ) + [
            'user_id' => $userId,
            'provider_id' => $providerId,
            'compra_id' => $compraId,
            'producto_id' => $productoId,
            'variante_id' => $varianteId,
            'compra_item_id' => $compraItemId,
            'recepcion_id' => $recepcionId,
            'recepcion_item_id' => $recepcionItemId,
        ];
    }

    private function claimMigrations(): array
    {
        $paths = glob(database_path('migrations/2026_10_05_16000*.php')) ?: [];
        sort($paths);

        return array_map(static function (string $path) {
            return require $path;
        }, $paths);
    }

    private function runClaimMigrations(): void
    {
        foreach ($this->claimMigrations() as $migration) {
            $migration->up();
        }

        $reposicionMigration = require database_path(
            'migrations/2026_10_06_100000_create_proveedor_reclamo_reposiciones_table.php'
        );
        $reposicionMigration->up();
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('accion', 80);
            $table->string('entidad', 120)->nullable();
            $table->string('entidad_id', 120)->nullable();
            $table->json('valores_antes')->nullable();
            $table->json('valores_despues')->nullable();
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table): void {
            $table->id();
            $table->string('tienda_proveedor');
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->string('estado_compra')->default('1');
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_venta_producto')->default(0);
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('compra_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla')->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('stock_ingresado')->default(0);
            $table->timestamps();
        });

        Schema::create('compra_recepciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->dateTime('fecha_recepcion');
            $table->timestamps();
        });

        Schema::create('compra_recepcion_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_recepcion_id')->constrained('compra_recepciones');
            $table->foreignId('compra_item_id')->constrained('compra_items');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_aceptada')->default(0);
            $table->unsignedInteger('cantidad_danada')->default(0);
            $table->unsignedInteger('cantidad_incorrecta')->default(0);
            $table->unsignedInteger('cantidad_faltante_declarada')->default(0);
            $table->boolean('cierra_item')->default(false);
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('tipo_movimiento', 40);
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->unsignedBigInteger('compra_item_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_item_id')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
