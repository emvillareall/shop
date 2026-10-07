<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosPublicationIndependenceFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction(
            'CONCAT',
            static fn (...$values): string => implode('', $values)
        );

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

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->string('cedula_clientes')->nullable();
            $table->string('nombres_clientes')->nullable();
            $table->string('apellidos_clientes')->nullable();
            $table->timestamps();
        });

        Schema::create('tiendas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_tienda');
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
            $table->string('talla_por_color')->nullable();
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });
    }

    public function test_pos_lists_active_product_even_when_ecommerce_publication_is_false(): void
    {
        $colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productoId = DB::table('productos')->insertGetId([
            'descripcion_producto' => 'Producto POS no publicado',
            'stock_venta_producto' => 10,
            'precio_venta_producto' => 20,
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'producto_id' => $productoId,
            'colores_id' => $colorId,
            'talla_por_color' => 'M',
            'stock_por_color' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = (new User())->forceFill([
            'id' => 1,
            'estado_user' => '1',
            'acceso_administrativo_completo' => true,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/home')
            ->assertOk()
            ->assertSee('Producto POS no publicado');
    }
}
