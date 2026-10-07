<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Models\InventarioMovimiento;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryReceptionSchemaTest extends TestCase
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

    public function test_reception_schema_and_eloquent_relations_work(): void
    {
        $this->assertTrue(Schema::hasColumn('compras', 'estado_recepcion'));
        $compraColumns = collect(Schema::getColumns('compras'))->keyBy('name');
        $this->assertFalse($compraColumns['estado_recepcion']['nullable']);
        $this->assertSame(
            'EN_TRANSITO',
            trim((string) $compraColumns['estado_recepcion']['default'], "'\"")
        );
        $this->assertTrue(Schema::hasTable('compra_recepciones'));
        $this->assertTrue(Schema::hasTable('compra_recepcion_items'));

        $userId = DB::table('users')->insertGetId([
            'name' => 'Reception Test User',
            'email' => 'reception-schema@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $compraId = $this->createPurchase();
        $compraItemId = DB::table('compra_items')->insertGetId([
            'compra_id' => $compraId,
            'cantidad' => 12,
            'stock_ingresado' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recepcion = CompraRecepcion::create([
            'compra_id' => $compraId,
            'user_id' => $userId,
            'fecha_recepcion' => now(),
        ]);
        $recepcionItem = CompraRecepcionItem::create([
            'compra_recepcion_id' => $recepcion->id,
            'compra_item_id' => $compraItemId,
        ]);
        $recepcionItem->refresh();

        $compra = Compra::findOrFail($compraId);
        $compraItem = CompraItem::findOrFail($compraItemId);

        $this->assertSame($compraId, $recepcion->compra->id);
        $this->assertSame($userId, $recepcion->user->id);
        $this->assertSame($recepcion->id, $compra->recepciones->first()->id);
        $this->assertSame($recepcion->id, $recepcionItem->compraRecepcion->id);
        $this->assertSame($compraItemId, $recepcionItem->compraItem->id);
        $this->assertSame($recepcionItem->id, $compraItem->recepciones->first()->id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $recepcion->fecha_recepcion);
        $this->assertSame(0, $recepcionItem->cantidad_recibida);
        $this->assertSame(0, $recepcionItem->cantidad_aceptada);
        $this->assertSame(0, $recepcionItem->cantidad_danada);
        $this->assertSame(0, $recepcionItem->cantidad_incorrecta);
        $this->assertSame(0, $recepcionItem->cantidad_faltante_declarada);
        $this->assertFalse($recepcionItem->cierra_item);
    }

    public function test_reception_defaults_nullable_idempotency_and_movement_links(): void
    {
        $columns = collect(Schema::getColumns('compra_recepciones'))
            ->keyBy('name');
        $itemColumns = collect(Schema::getColumns('compra_recepcion_items'))
            ->keyBy('name');
        $movementColumns = collect(Schema::getColumns('inventario_movimientos'))
            ->keyBy('name');

        $this->assertTrue($columns['idempotency_key']['nullable']);
        $this->assertTrue($columns['user_id']['nullable']);
        foreach ([
            'cantidad_recibida',
            'cantidad_aceptada',
            'cantidad_danada',
            'cantidad_incorrecta',
            'cantidad_faltante_declarada',
        ] as $name) {
            $this->assertSame(0, (int) $itemColumns[$name]['default'], $name);
        }
        $this->assertSame(0, (int) $itemColumns['cierra_item']['default']);
        $this->assertFalse($itemColumns['cierra_item']['nullable']);

        foreach ([
            'compra_item_id',
            'compra_recepcion_id',
            'compra_recepcion_item_id',
        ] as $name) {
            $this->assertTrue($movementColumns[$name]['nullable'], $name);
        }

        $compraId = $this->createPurchase();
        $productoId = DB::table('productos')->insertGetId([
            'stock_venta_producto' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => 1,
            'talla_por_color' => 'M',
            'stock_por_color' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventario_movimientos')->insert([
            'producto_id' => $productoId,
            'tipo_movimiento' => 'ENTRADA_COMPRA',
            'cantidad' => 9,
            'stock_antes' => 0,
            'stock_despues' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = [
            'movimientos' => DB::table('inventario_movimientos')->count(),
            'stock_producto' => DB::table('productos')->whereKey($productoId)->value('stock_venta_producto'),
            'stock_variante' => DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'),
        ];

        $recepcion = CompraRecepcion::create([
            'compra_id' => $compraId,
            'fecha_recepcion' => now(),
        ]);
        CompraRecepcion::create([
            'compra_id' => $compraId,
            'fecha_recepcion' => now(),
        ]);

        $this->assertSame($before['movimientos'], DB::table('inventario_movimientos')->count());
        $this->assertSame($before['stock_producto'], DB::table('productos')->whereKey($productoId)->value('stock_venta_producto'));
        $this->assertSame($before['stock_variante'], DB::table('colores_productos')->where('producto_id', $productoId)->value('stock_por_color'));
        $this->assertSame(0, DB::table('compra_recepcion_items')->count());
        $this->assertSame(2, DB::table('compra_recepciones')->whereNull('idempotency_key')->count());
        $this->assertNull($recepcion->idempotency_key);
        $this->assertNull(InventarioMovimiento::query()->first()->compraItem);
        $this->assertNull(InventarioMovimiento::query()->first()->compraRecepcion);
        $this->assertNull(InventarioMovimiento::query()->first()->compraRecepcionItem);
    }

    public function test_variant_unique_index_is_present_after_zero_duplicate_validation(): void
    {
        $this->assertTrue(Schema::hasIndex(
            'colores_productos',
            'colores_productos_producto_color_talla_unique'
        ));
    }

    private function createPurchase(): int
    {
        return DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-'.uniqid(),
            'descripcion_compra' => 'Compra de prueba',
            'estado_compra' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runReceptionMigrations(): void
    {
        foreach (glob(database_path('migrations/2026_10_01_1717*.php')) as $path) {
            $migration = require $path;
            $migration->up();
        }

        $migration = require database_path('migrations/2026_10_05_131323_harden_estado_recepcion_on_compras_table.php');
        $migration->up();
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
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_venta_producto')->default(0);
            $table->timestamps();
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->integer('cantidad')->default(0);
            $table->integer('stock_ingresado')->default(0);
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
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->string('tipo_movimiento', 40);
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->timestamps();
        });
    }
}
