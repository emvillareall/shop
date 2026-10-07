<?php

namespace Tests\Feature;

use App\Livewire\Ecommerce\CatalogoProductos;
use App\Models\Producto;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EcommerceVisibilityFeatureTest extends TestCase
{
    private int $lineaId;
    private int $categoriaId;
    private int $colorId;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('security.storefront_open', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        $this->createSchema();

        $this->lineaId = DB::table('lineas_ropa')->insertGetId([
            'nombre_linea' => 'Linea visibilidad',
            'estado_linea' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->categoriaId = DB::table('categorias_productos')->insertGetId([
            'nombre_categoria' => 'Categoria visibilidad',
            'linea_ropa_id' => $this->lineaId,
            'estado_categoria' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->colorId = DB::table('colores')->insertGetId([
            'nombre_color' => 'Negro',
            'codigo_color' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_visible_scope_requires_active_and_published_but_not_stock(): void
    {
        $visible = $this->createProduct([
            'descripcion_producto' => 'Visible sin stock',
            'stock_venta_producto' => 0,
            'publicado_ecommerce' => true,
        ]);
        $hidden = $this->createProduct([
            'descripcion_producto' => 'Oculto con stock',
            'stock_venta_producto' => 10,
            'publicado_ecommerce' => false,
        ]);
        $inactive = $this->createProduct([
            'descripcion_producto' => 'Inactivo publicado',
            'stock_venta_producto' => 10,
            'estado_producto' => 0,
            'publicado_ecommerce' => true,
        ]);

        $this->assertSame([$visible->id], Producto::query()->visibleEcommerce()->pluck('id')->all());
        $this->assertNotNull(Producto::query()->find($hidden->id));
        $this->assertNotNull(Producto::query()->find($inactive->id));
    }

    public function test_modern_catalog_search_and_filters_use_the_visibility_scope(): void
    {
        $visible = $this->createProduct([
            'descripcion_producto' => 'Visible agotado unico',
            'codigo_producto' => 'VISIBLE-001',
            'stock_venta_producto' => 0,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($visible, 0, 'M');

        $hidden = $this->createProduct([
            'descripcion_producto' => 'Termino oculto unico',
            'codigo_producto' => 'OCULTO-001',
            'stock_venta_producto' => 10,
            'publicado_ecommerce' => false,
        ]);
        $this->addVariant($hidden, 10, 'L');

        $inactive = $this->createProduct([
            'descripcion_producto' => 'Termino inactivo unico',
            'codigo_producto' => 'INACTIVO-001',
            'stock_venta_producto' => 10,
            'estado_producto' => 0,
            'publicado_ecommerce' => true,
        ]);
        $this->addVariant($inactive, 10, 'S');

        $component = Livewire::test(CatalogoProductos::class)
            ->assertSee('Visible agotado unico')
            ->assertSee('Agotado')
            ->assertDontSee('Termino oculto unico')
            ->assertDontSee('Termino inactivo unico');

        $component->set('search', 'Termino oculto unico')
            ->assertDontSee('Termino oculto unico');

        $component->set('search', 'Visible agotado unico')
            ->assertSee('Visible agotado unico');

        $component->set('search', '')
            ->set('categoria', (string) $this->categoriaId)
            ->set('linea', (string) $this->lineaId)
            ->assertSee('Visible agotado unico')
            ->assertDontSee('Termino oculto unico')
            ->assertDontSee('Termino inactivo unico');
    }

    public function test_public_detail_requires_an_active_published_product(): void
    {
        $visible = $this->createProduct(['descripcion_producto' => 'Detalle visible']);
        $hidden = $this->createProduct([
            'descripcion_producto' => 'Detalle oculto',
            'publicado_ecommerce' => false,
        ]);
        $inactive = $this->createProduct([
            'descripcion_producto' => 'Detalle inactivo',
            'estado_producto' => 0,
        ]);

        $this->get(route('ecommerce.productos.show', $visible))->assertOk()->assertSee('Detalle visible');
        $this->get(route('ecommerce.productos.show', $hidden))->assertNotFound();
        $this->get(route('ecommerce.productos.show', $inactive))->assertNotFound();
    }

    public function test_public_variant_api_requires_an_active_published_product(): void
    {
        $visible = $this->createProduct(['descripcion_producto' => 'API visible']);
        $hidden = $this->createProduct([
            'descripcion_producto' => 'API oculto',
            'publicado_ecommerce' => false,
        ]);
        $inactive = $this->createProduct([
            'descripcion_producto' => 'API inactivo',
            'estado_producto' => 0,
        ]);

        $this->addVariant($visible, 3, 'M');
        $this->addVariant($hidden, 3, 'L');
        $this->addVariant($inactive, 3, 'S');

        $this->getJson(route('ecommerce.productos.variantes', $visible))
            ->assertOk()
            ->assertJsonFragment(['talla_por_color' => 'M']);
        $this->getJson(route('ecommerce.productos.variantes', $hidden))->assertNotFound();
        $this->getJson(route('ecommerce.productos.variantes', $inactive))->assertNotFound();
    }

    public function test_public_pdf_catalog_uses_the_visibility_scope(): void
    {
        $visible = $this->createProduct(['descripcion_producto' => 'PDF visible']);
        $hidden = $this->createProduct([
            'descripcion_producto' => 'PDF oculto',
            'publicado_ecommerce' => false,
        ]);
        $this->addVariant($visible, 2, 'M');
        $this->addVariant($hidden, 2, 'L');

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $response = $this->get('/catalogo/descargar');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertTrue(collect($queries)->contains(
            fn (string $sql): bool => str_contains($sql, 'publicado_ecommerce')
        ));
    }

    private function createProduct(array $overrides = []): Producto
    {
        $published = array_key_exists('publicado_ecommerce', $overrides)
            ? (bool) $overrides['publicado_ecommerce']
            : true;

        unset($overrides['publicado_ecommerce']);

        $producto = new Producto();
        $producto->fill(array_merge([
            'codigo_producto' => 'COD-' . uniqid(),
            'descripcion_producto' => 'Producto visibilidad',
            'cantidad_compra_producto' => 1,
            'stock_venta_producto' => 0,
            'precio_pesos_producto' => 100,
            'precio_dolares_producto' => 25,
            'precio_venta_producto' => 100,
            'promocion_activa' => false,
            'categoria_producto_id' => $this->categoriaId,
            'estado_producto' => 1,
        ], $overrides));
        $producto->publicado_ecommerce = $published;
        $producto->save();

        return $producto->fresh();
    }

    private function addVariant(Producto $producto, int $stock, string $talla): void
    {
        DB::table('colores_productos')->insert([
            'producto_id' => $producto->id,
            'colores_id' => $this->colorId,
            'talla_por_color' => $talla,
            'cantidad_por_color' => $stock,
            'stock_por_color' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('lineas_ropa', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_linea');
            $table->boolean('estado_linea')->default(true);
            $table->timestamps();
        });

        Schema::create('categorias_productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_categoria');
            $table->unsignedBigInteger('linea_ropa_id');
            $table->boolean('estado_categoria')->default(true);
            $table->timestamps();
        });

        Schema::create('colores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_color');
            $table->string('codigo_color');
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_producto');
            $table->string('descripcion_producto');
            $table->integer('cantidad_compra_producto')->default(0);
            $table->integer('stock_venta_producto')->default(0);
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->decimal('precio_promocional', 12, 2)->nullable();
            $table->boolean('promocion_activa')->default(false);
            $table->datetime('promocion_fecha_inicio')->nullable();
            $table->datetime('promocion_fecha_fin')->nullable();
            $table->string('promocion_etiqueta')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->unsignedBigInteger('compras_id')->nullable();
            $table->unsignedBigInteger('categoria_producto_id')->nullable();
            $table->integer('estado_producto')->default(1);
            $table->boolean('publicado_ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla_por_color')->nullable();
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('color_normalizado')->nullable();
            $table->string('ruta')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
}
