<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Tests\TestCase;

class PublicationClosureFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('security.storefront_open', true);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion_producto')->nullable();
            $table->integer('estado_producto')->default(1);
            $table->boolean('publicado_ecommerce')->default(false);
            $table->string('imagen_producto')->nullable();
            $table->timestamps();
        });

        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('ruta');
            $table->timestamps();
        });
    }

    public function test_legacy_catalog_redirects_to_modern_catalog(): void
    {
        $this->get('/catalogo')
            ->assertRedirect(route('ecommerce.productos.index'));
    }

    public function test_hidden_linked_product_media_returns_not_found(): void
    {
        $productoId = DB::table('productos')->insertGetId([
            'descripcion_producto' => 'Producto oculto con imagen',
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('producto_imagenes')->insert([
            'producto_id' => $productoId,
            'ruta' => 'imagenes/logo.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/media/productos/imagenes/logo.png')->assertNotFound();
    }

    public function test_published_linked_product_media_remains_available(): void
    {
        $productoId = DB::table('productos')->insertGetId([
            'descripcion_producto' => 'Producto publicado con imagen',
            'estado_producto' => 1,
            'publicado_ecommerce' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('producto_imagenes')->insert([
            'producto_id' => $productoId,
            'ruta' => 'imagenes/logo.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/media/productos/imagenes/logo.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_authenticated_internal_media_keeps_access_to_hidden_product_image(): void
    {
        $productoId = DB::table('productos')->insertGetId([
            'descripcion_producto' => 'Producto interno oculto con imagen',
            'estado_producto' => 1,
            'publicado_ecommerce' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('producto_imagenes')->insert([
            'producto_id' => $productoId,
            'ruta' => 'imagenes/logo.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = (new User())->forceFill(['id' => 1, 'name' => 'Backoffice']);

        $this->actingAs($user)
            ->get('/media/productos/imagenes/logo.png')
            ->assertOk();
    }

    public function test_unlinked_global_media_remains_available(): void
    {
        $this->get('/media/productos/imagenes/logo.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
