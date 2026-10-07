<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductAuthorizationFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_scoped_user_can_manage_products_without_delete_or_other_admin_access(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $lineaId = DB::table('lineas_ropa')->insertGetId([
            'nombre_linea' => 'Linea de prueba',
        ]);
        $categoriaId = DB::table('categorias_productos')->insertGetId([
            'nombre_categoria' => 'Categoria de prueba',
            'linea_ropa_id' => $lineaId,
        ]);
        $compraId = DB::table('compras')->insertGetId([
            'codigo_compra' => 'COMPRA-TEST',
            'descripcion_compra' => 'Compra de prueba',
        ]);
        $producto = Producto::query()->create([
            'codigo_producto' => 'PRODUCTO-TEST',
            'descripcion_producto' => 'Producto de prueba',
            'cantidad_compra_producto' => '1',
            'stock_venta_producto' => '1',
            'precio_pesos_producto' => 1,
            'precio_dolares_producto' => 1,
            'precio_venta_producto' => 1,
            'compras_id' => $compraId,
            'categoria_producto_id' => $categoriaId,
            'estado_producto' => '1',
        ]);

        $this->actingAs($user)
            ->get('/productos')
            ->assertOk()
            ->assertSee('Agregar producto')
            ->assertSee('Editar')
            ->assertDontSee('Eliminar');

        $this->actingAs($user)
            ->get('/productos/create')
            ->assertOk()
            ->assertSee('Compra asociada');

        $this->actingAs($user)->post('/productos', [
            'codigo_producto' => 'PRODUCTO-CREADO',
            'descripcion_producto' => 'Producto creado por prueba autorizada',
            'cantidad_compra_producto' => 2,
            'stock_venta_producto' => 2,
            'precio_pesos_producto' => 5,
            'precio_dolares_producto' => 5,
            'precio_venta_producto' => 8,
            'compras_id' => $compraId,
            'categoria_producto_id' => $categoriaId,
            'estado_producto' => '1',
            'imagen' => UploadedFile::fake()->image('producto.jpg', 120, 120),
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'codigo_producto' => 'PRODUCTO-CREADO',
            'publicado_ecommerce' => 0,
        ]);

        $this->actingAs($user)
            ->get('/productos/'.$producto->id.'/edit')
            ->assertOk();

        $this->actingAs($user)->patch('/productos/'.$producto->id, [
            'codigo_producto' => 'PRODUCTO-TEST',
            'descripcion_producto' => 'Producto actualizado por prueba autorizada',
            'cantidad_compra_producto' => 1,
            'stock_venta_producto' => 1,
            'precio_pesos_producto' => 1,
            'precio_dolares_producto' => 1,
            'precio_venta_producto' => 2,
            'compras_id' => $compraId,
            'categoria_producto_id' => $categoriaId,
            'estado_producto' => '1',
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'descripcion_producto' => 'Producto actualizado por prueba autorizada',
            'publicado_ecommerce' => 0,
        ]);

        $this->actingAs($user)
            ->delete('/productos/'.$producto->id)
            ->assertForbidden();

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);

        foreach (['/home', '/clientes', '/compras', '/pedidos', '/ventas', '/pagos', '/auditoria'] as $uri) {
            $this->actingAs($user)->get($uri)->assertForbidden();
        }
    }

    public function test_scoped_user_login_redirects_to_products(): void
    {
        $user = $this->createUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('productos.index'));
    }

    public function test_existing_full_access_user_keeps_product_and_admin_permissions(): void
    {
        $admin = $this->createUser([
            'email' => 'legacy-admin@example.test',
            'acceso_administrativo_completo' => true,
            'puede_ver_productos' => false,
            'puede_crear_productos' => false,
            'puede_editar_productos' => false,
        ]);
        $producto = new Producto();

        $this->assertTrue(Gate::forUser($admin)->allows('administracion-completa'));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Producto::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Producto::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $producto));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $producto));
    }

    private function createUser(array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Scoped Product User',
            'email' => 'scoped-products@example.test',
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

    private function createSchema(): void
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

        Schema::create('lineas_ropa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_linea');
            $table->timestamps();
        });

        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_categoria');
            $table->unsignedBigInteger('linea_ropa_id')->nullable();
            $table->string('estado_categoria')->default('1');
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
            $table->timestamps();
        });

        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_parametro')->unique();
            $table->decimal('valor_parametro', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto');
            $table->string('descripcion_producto');
            $table->string('cantidad_compra_producto');
            $table->string('stock_venta_producto')->default('0');
            $table->decimal('precio_pesos_producto', 12, 2)->default(0);
            $table->decimal('precio_dolares_producto', 12, 2)->default(0);
            $table->decimal('precio_venta_producto', 12, 2)->default(0);
            $table->decimal('precio_promocional', 12, 2)->nullable();
            $table->boolean('promocion_activa')->default(false);
            $table->dateTime('promocion_fecha_inicio')->nullable();
            $table->dateTime('promocion_fecha_fin')->nullable();
            $table->string('promocion_etiqueta')->nullable();
            $table->string('imagen_producto')->nullable();
            $table->unsignedBigInteger('compras_id');
            $table->unsignedBigInteger('categoria_producto_id')->nullable();
            $table->string('estado_producto')->default('1');
            $table->boolean('publicado_ecommerce')->default(false);
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
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla_por_color')->nullable();
            $table->integer('cantidad_por_color')->default(0);
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('ruta')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('color_nombre')->nullable();
            $table->string('color_normalizado')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('peso_original')->nullable();
            $table->unsignedBigInteger('peso_optimizado')->nullable();
            $table->unsignedInteger('ancho')->nullable();
            $table->unsignedInteger('alto')->nullable();
            $table->timestamps();
        });
    }
}
