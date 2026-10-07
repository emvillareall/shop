<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicationFlagFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('estado_producto')->default('1');
            $table->integer('stock_venta_producto')->default(0);
            $table->timestamps();
        });
    }

    public function test_migration_backfills_only_products_visible_under_the_current_rule(): void
    {
        DB::table('productos')->insert([
            ['id' => 1, 'estado_producto' => '1', 'stock_venta_producto' => 7],
            ['id' => 2, 'estado_producto' => '0', 'stock_venta_producto' => 9],
        ]);

        $before = DB::table('productos')->pluck('stock_venta_producto', 'id')->all();
        $migration = require database_path('migrations/2026_10_05_150000_add_publicado_ecommerce_to_productos_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('productos', 'publicado_ecommerce'));
        $this->assertSame(1, (int) DB::table('productos')->where('id', 1)->value('publicado_ecommerce'));
        $this->assertSame(0, (int) DB::table('productos')->where('id', 2)->value('publicado_ecommerce'));
        $this->assertSame($before, DB::table('productos')->pluck('stock_venta_producto', 'id')->all());
        $this->assertSame('1', (string) DB::table('productos')->where('id', 1)->value('estado_producto'));
        $this->assertSame('0', (string) DB::table('productos')->where('id', 2)->value('estado_producto'));
    }

    public function test_new_rows_default_to_not_published_and_cast_to_boolean(): void
    {
        $migration = require database_path('migrations/2026_10_05_150000_add_publicado_ecommerce_to_productos_table.php');
        $migration->up();

        DB::table('productos')->insert([
            'estado_producto' => '1',
            'stock_venta_producto' => 0,
        ]);

        $this->assertSame(0, (int) DB::table('productos')->latest('id')->value('publicado_ecommerce'));
        $this->assertContains(Schema::getColumnType('productos', 'publicado_ecommerce'), ['tinyint', 'integer', 'boolean']);
        $this->assertFalse(Producto::query()->latest('id')->firstOrFail()->publicado_ecommerce);
    }

    public function test_down_drops_only_the_publication_column(): void
    {
        DB::table('productos')->insert([
            'estado_producto' => '1',
            'stock_venta_producto' => 11,
        ]);

        $migration = require database_path('migrations/2026_10_05_150000_add_publicado_ecommerce_to_productos_table.php');
        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasColumn('productos', 'publicado_ecommerce'));
        $this->assertSame('1', (string) DB::table('productos')->value('estado_producto'));
        $this->assertSame(11, (int) DB::table('productos')->value('stock_venta_producto'));
    }
}
