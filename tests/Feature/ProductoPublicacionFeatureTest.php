<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductoPublicacionFeatureTest extends TestCase
{
    private int $categoriaId;

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

        $lineaId = DB::table('lineas_ropa')->insertGetId([
            'nombre_linea' => 'Linea administrativa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->categoriaId = DB::table('categorias_productos')->insertGetId([
            'nombre_categoria' => 'Categoria administrativa',
            'linea_ropa_id' => $lineaId,
            'estado_categoria' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_authorized_user_can_publish_and_hide_without_changing_operational_fields(): void
    {
        $user = $this->createUser();
        $producto = $this->createProduct([
            'stock_venta_producto' => 10,
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
        ]);
        $varianteId = DB::table('colores_productos')->insertGetId([
            'producto_id' => $producto->id,
            'talla_por_color' => 'M',
            'cantidad_por_color' => 7,
            'stock_por_color' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from('/productos')
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect('/productos')
            ->assertSessionHas('success', 'Producto publicado en ecommerce correctamente.');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'publicado_ecommerce' => 1,
            'estado_producto' => 1,
            'stock_venta_producto' => 10,
        ]);
        $this->assertSame([$producto->id], Producto::query()->visibleEcommerce()->pluck('id')->all());

        $this->actingAs($user)
            ->from('/productos')
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect('/productos');

        $this->actingAs($user)
            ->from('/productos')
            ->patch(route('productos.ocultar', $producto))
            ->assertRedirect('/productos')
            ->assertSessionHas('success', 'Producto ocultado del ecommerce correctamente.');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'publicado_ecommerce' => 0,
            'estado_producto' => 1,
            'stock_venta_producto' => 10,
        ]);
        $this->assertDatabaseHas('colores_productos', [
            'id' => $varianteId,
            'stock_por_color' => 7,
        ]);
        $this->assertSame([], Producto::query()->visibleEcommerce()->pluck('id')->all());
    }

    public function test_publish_and_hide_are_reflected_immediately_by_public_surfaces(): void
    {
        $user = $this->createUser();
        $producto = $this->createProduct([
            'descripcion_producto' => 'Producto integrado de publicacion',
            'publicado_ecommerce' => false,
        ]);

        $this->get('/shop/catalogo')
            ->assertOk()
            ->assertDontSee('Producto integrado de publicacion');
        $this->get(route('ecommerce.productos.show', $producto))->assertNotFound();
        $this->getJson(route('ecommerce.productos.variantes', $producto))->assertNotFound();

        $this->actingAs($user)
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect();

        $this->get('/shop/catalogo')
            ->assertOk()
            ->assertSee('Producto integrado de publicacion');
        $this->get(route('ecommerce.productos.show', $producto))
            ->assertOk()
            ->assertSee('Producto integrado de publicacion');
        $this->getJson(route('ecommerce.productos.variantes', $producto))
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($user)
            ->patch(route('productos.ocultar', $producto))
            ->assertRedirect();

        $this->get('/shop/catalogo')
            ->assertOk()
            ->assertDontSee('Producto integrado de publicacion');
        $this->get(route('ecommerce.productos.show', $producto))->assertNotFound();
        $this->getJson(route('ecommerce.productos.variantes', $producto))->assertNotFound();
    }

    public function test_user_without_update_permission_cannot_publish_or_hide_and_full_admin_keeps_bypass(): void
    {
        $user = $this->createUser(['puede_editar_productos' => false]);
        $producto = $this->createProduct(['publicado_ecommerce' => false]);

        $this->actingAs($user)
            ->patch(route('productos.publicar', $producto))
            ->assertForbidden();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'publicado_ecommerce' => 0,
        ]);

        $admin = $this->createUser([
            'email' => 'full-admin@example.test',
            'acceso_administrativo_completo' => true,
            'puede_ver_productos' => false,
            'puede_editar_productos' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'publicado_ecommerce' => 1,
        ]);
    }

    public function test_inactive_product_cannot_be_published_but_can_be_hidden(): void
    {
        $user = $this->createUser();
        $producto = $this->createProduct([
            'estado_producto' => 0,
            'publicado_ecommerce' => false,
        ]);

        $this->actingAs($user)
            ->from('/productos')
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect('/productos')
            ->assertSessionHasErrors([
                'publicado_ecommerce' => 'El producto debe estar activo antes de publicarlo.',
            ]);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'estado_producto' => 0,
            'publicado_ecommerce' => 0,
        ]);

        DB::table('productos')->where('id', $producto->id)->update(['publicado_ecommerce' => true]);

        $this->actingAs($user)
            ->from('/productos')
            ->patch(route('productos.ocultar', $producto))
            ->assertRedirect('/productos');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'estado_producto' => 0,
            'publicado_ecommerce' => 0,
        ]);
    }

    public function test_stock_zero_can_be_published_and_enters_the_visibility_scope(): void
    {
        $user = $this->createUser();
        $producto = $this->createProduct([
            'stock_venta_producto' => 0,
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('productos.publicar', $producto))
            ->assertRedirect();

        $producto = $producto->fresh();

        $this->assertTrue((bool) $producto->publicado_ecommerce);
        $this->assertSame(0, (int) $producto->stock_venta_producto);
        $this->assertTrue(Producto::query()->visibleEcommerce()->whereKey($producto->id)->exists());
    }

    public function test_admin_listing_shows_publication_state_and_hides_actions_without_permission(): void
    {
        $authorized = $this->createUser();
        $hidden = $this->createProduct([
            'descripcion_producto' => 'Producto no publicado',
            'publicado_ecommerce' => false,
        ]);
        $published = $this->createProduct([
            'descripcion_producto' => 'Producto publicado',
            'publicado_ecommerce' => true,
        ]);

        $this->actingAs($authorized)
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSee('NO PUBLICADO')
            ->assertSee('PUBLICADO')
            ->assertSee('Publicar')
            ->assertSee('Ocultar');

        $readonly = $this->createUser([
            'email' => 'readonly-products@example.test',
            'puede_editar_productos' => false,
        ]);

        $this->actingAs($readonly)
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSee('NO PUBLICADO')
            ->assertSee('PUBLICADO')
            ->assertDontSee('Publicar')
            ->assertDontSee('Ocultar');

        $this->assertNotNull($hidden->id);
        $this->assertNotNull($published->id);
    }

    public function test_publication_routes_require_authentication(): void
    {
        $producto = $this->createProduct();

        $this->patch(route('productos.publicar', $producto))->assertRedirect(route('login'));
        $this->patch(route('productos.ocultar', $producto))->assertRedirect(route('login'));
    }

    private function createUser(array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Product Publication User',
            'email' => 'publication-user@example.test',
            'password' => Hash::make('test-password'),
            'estado_user' => '1',
            'acceso_administrativo_completo' => false,
            'puede_ver_productos' => true,
            'puede_crear_productos' => true,
            'puede_editar_productos' => true,
        ], $overrides));
        $user->save();

        return $user->refresh();
    }

    private function createProduct(array $overrides = []): Producto
    {
        $published = array_key_exists('publicado_ecommerce', $overrides)
            ? (bool) $overrides['publicado_ecommerce']
            : false;
        unset($overrides['publicado_ecommerce']);

        $producto = new Producto();
        $producto->fill(array_merge([
            'codigo_producto' => 'PUB-' . uniqid(),
            'descripcion_producto' => 'Producto de publicacion',
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

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
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

        Schema::create('lineas_ropa', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_linea');
            $table->string('estado_linea')->default('1');
            $table->timestamps();
        });

        Schema::create('categorias_productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_categoria');
            $table->unsignedBigInteger('linea_ropa_id')->nullable();
            $table->string('estado_categoria')->default('1');
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
            $table->dateTime('promocion_fecha_inicio')->nullable();
            $table->dateTime('promocion_fecha_fin')->nullable();
            $table->string('promocion_etiqueta')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->unsignedBigInteger('compras_id')->nullable();
            $table->unsignedBigInteger('categoria_producto_id')->nullable();
            $table->string('estado_producto')->default('1');
            $table->boolean('publicado_ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('colores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_color');
            $table->string('codigo_color')->nullable();
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
            $table->string('ruta')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('color_normalizado')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
}
