<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityRoutesFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useSqliteInMemory();
        $this->createSchema();
    }

    private function useSqliteInMemory(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->string('metodo')->nullable();
            $table->string('estado')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('moneda')->nullable();
            $table->timestamps();
        });
    }

    public function test_legacy_cart_aliases_reuse_modern_flow_routes(): void
    {
        $this->post('/carrito/agregar', [])->assertSessionHasErrors();
        $this->post('/carrito/vaciar', [])->assertRedirect();
        $this->post('/carrito/eliminar/0', [])->assertRedirect();
    }

    public function test_legacy_id_colores_endpoint_is_gone(): void
    {
        $this->get('/id_colores/1')->assertStatus(410);
    }

    public function test_pdf_legacy_alias_redirects_to_signed_route(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user)->get('/pdf_pedidos/1')->assertRedirect();
    }

    public function test_payment_return_requires_valid_signature(): void
    {
        DB::table('pagos')->insert([
            'id' => 10,
            'pedido_id' => 1,
            'metodo' => 'paypal',
            'estado' => 'PENDIENTE',
            'monto' => 10.00,
            'moneda' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/payments/paypal/10/return')->assertForbidden();
    }
}
