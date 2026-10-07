<?php

namespace Tests\Feature;

use App\Console\Commands\NormalizeHistoricalPurchaseReceptionsCommand;
use App\Models\Compra;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizeHistoricalPurchaseReceptionsCommandTest extends TestCase
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

    public function test_mode_is_required_and_dry_run_is_read_only(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5], [3, 3]]);
        $before = $this->inventorySnapshot();

        $this->artisan('compras:normalizar-recepciones-historicas')
            ->expectsOutput('Debes especificar exactamente una opción: --dry-run o --apply.')
            ->assertExitCode(1);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--dry-run' => true])
            ->expectsOutput('Compras históricas analizadas: 1')
            ->expectsOutput('SEGURA_PARA_NORMALIZAR: 1 | IDs: '.$compra->id)
            ->expectsOutput('REQUIERE_REVISION: 0 | IDs: ninguno')
            ->expectsOutput('ANULADA: 0 | IDs: ninguno')
            ->expectsOutput('INDETERMINADA: 0 | IDs: ninguno')
            ->expectsOutput('Recepciones que se crearían: 1')
            ->expectsOutput('Items de recepción que se crearían: 2')
            ->expectsOutput('Unidades recibidas sintéticas: 8')
            ->expectsOutput('Unidades aceptadas sintéticas: 8')
            ->expectsOutput('Movimientos a crear: 0')
            ->expectsOutput('Cambio esperado en stock: 0')
            ->expectsOutput('Cambio esperado stock_ingresado: 0')
            ->expectsOutput('Compras que quedarían RECIBIDA: 1')
            ->expectsOutput('DRY-RUN: no se realizaron escrituras.')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('compra_recepciones')->count());
        $this->assertSame(0, DB::table('compra_recepcion_items')->count());
        $this->assertNull(DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame($before, $this->inventorySnapshot());
    }

    public function test_both_modes_are_rejected_without_writes(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5]]);

        $this->artisan('compras:normalizar-recepciones-historicas', [
            '--dry-run' => true,
            '--apply' => true,
        ])->expectsOutput('Debes especificar exactamente una opción: --dry-run o --apply.')
            ->assertExitCode(1);

        $this->assertNull(DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(0, DB::table('compra_recepciones')->count());
    }

    public function test_apply_creates_only_synthetic_reception_data(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5], [3, 3]]);
        $before = $this->inventorySnapshot();

        $this->artisan('compras:normalizar-recepciones-historicas', ['--apply' => true])
            ->expectsOutput('Compras normalizadas: 1')
            ->assertExitCode(0);

        $recepcion = DB::table('compra_recepciones')->where('compra_id', $compra->id)->first();
        $this->assertNotNull($recepcion);
        $this->assertNull($recepcion->user_id);
        $this->assertSame(
            NormalizeHistoricalPurchaseReceptionsCommand::historicalIdempotencyKey((int) $compra->id),
            $recepcion->idempotency_key
        );
        $this->assertSame(
            'Recepción histórica normalizada; no generó movimiento de inventario.',
            $recepcion->observacion
        );

        $items = DB::table('compra_recepcion_items')
            ->where('compra_recepcion_id', $recepcion->id)
            ->orderBy('compra_item_id')
            ->get();

        $this->assertCount(2, $items);
        $this->assertSame([5, 3], $items->pluck('cantidad_recibida')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame([5, 3], $items->pluck('cantidad_aceptada')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame([0, 0], $items->pluck('cantidad_danada')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame([0, 0], $items->pluck('cantidad_incorrecta')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame([0, 0], $items->pluck('cantidad_faltante_declarada')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame([1, 1], $items->pluck('cierra_item')->map(fn ($value): int => (int) $value)->all());
        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame($before, $this->inventorySnapshot());
    }

    public function test_apply_is_idempotent(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5], [3, 3]]);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--apply' => true])
            ->assertExitCode(0);
        $beforeSecondRun = $this->inventorySnapshot();

        $this->artisan('compras:normalizar-recepciones-historicas', ['--apply' => true])
            ->expectsOutput('Compras históricas analizadas: 0')
            ->expectsOutput('Recepciones que se crearían: 0')
            ->expectsOutput('Items de recepción que se crearían: 0')
            ->expectsOutput('Compras normalizadas: 0')
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('compra_recepciones')->where('compra_id', $compra->id)->count());
        $this->assertSame(2, DB::table('compra_recepcion_items')->count());
        $this->assertSame($beforeSecondRun, $this->inventorySnapshot());
    }

    public function test_quantity_and_stock_mismatch_requires_review(): void
    {
        $compra = $this->createHistoricalPurchase([[10, 9]]);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--dry-run' => true])
            ->expectsOutput('SEGURA_PARA_NORMALIZAR: 0 | IDs: ninguno')
            ->expectsOutput('REQUIERE_REVISION: 1 | IDs: '.$compra->id)
            ->assertExitCode(0);

        $this->assertNoNormalization($compra);
    }

    public function test_entry_quantity_mismatch_requires_review(): void
    {
        $compra = $this->createHistoricalPurchase([[10, 10]], [9]);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--dry-run' => true])
            ->expectsOutput('REQUIERE_REVISION: 1 | IDs: '.$compra->id)
            ->assertExitCode(0);

        $this->assertNoNormalization($compra);
    }

    public function test_cancelled_purchase_is_not_normalized(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5]], null, 'ANULADA');

        $this->artisan('compras:normalizar-recepciones-historicas', ['--dry-run' => true])
            ->expectsOutput('ANULADA: 1 | IDs: '.$compra->id)
            ->expectsOutput('Recepciones que se crearían: 0')
            ->assertExitCode(0);

        $this->assertNoNormalization($compra);
    }

    public function test_uuid_v5_is_stable_unique_and_valid_for_char_36(): void
    {
        $first = NormalizeHistoricalPurchaseReceptionsCommand::historicalIdempotencyKey(3);
        $same = NormalizeHistoricalPurchaseReceptionsCommand::historicalIdempotencyKey(3);
        $other = NormalizeHistoricalPurchaseReceptionsCommand::historicalIdempotencyKey(4);

        $this->assertSame($first, $same);
        $this->assertNotSame($first, $other);
        $this->assertSame(36, strlen($first));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $first
        );
    }

    public function test_existing_matching_reception_is_reused_without_duplication(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5], [3, 3]]);
        $this->createMatchingHistoricalReception($compra);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('compra_recepciones')->where('compra_id', $compra->id)->count());
        $this->assertSame(2, DB::table('compra_recepcion_items')->count());
        $this->assertSame('RECIBIDA', DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
    }

    public function test_existing_conflicting_reception_requires_review_and_is_not_repaired(): void
    {
        $compra = $this->createHistoricalPurchase([[5, 5]]);
        $recepcionId = $this->createMatchingHistoricalReception($compra);
        DB::table('compra_recepcion_items')
            ->where('compra_recepcion_id', $recepcionId)
            ->update(['cantidad_aceptada' => 4]);

        $this->artisan('compras:normalizar-recepciones-historicas', ['--dry-run' => true])
            ->expectsOutput('REQUIERE_REVISION: 1 | IDs: '.$compra->id)
            ->assertExitCode(0);

        $this->assertNull(DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(4, (int) DB::table('compra_recepcion_items')->value('cantidad_aceptada'));
    }

    private function assertNoNormalization(Compra $compra): void
    {
        $this->assertNull(DB::table('compras')->where('id', $compra->id)->value('estado_recepcion'));
        $this->assertSame(0, DB::table('compra_recepciones')->count());
        $this->assertSame(0, DB::table('compra_recepcion_items')->count());
    }

    /**
     * @param array<int, array{0:int,1:int}> $items
     * @param array<int, int>|null $entryQuantities
     */
    private function createHistoricalPurchase(
        array $items,
        ?array $entryQuantities = null,
        string $estadoCompra = '1'
    ): Compra {
        $createdAt = Carbon::parse('2026-09-04 12:00:00');
        $compraId = DB::table('compras')->insertGetId([
            'codigo_compra' => 'HIST-'.$estadoCompra.'-'.uniqid(),
            'descripcion_compra' => 'Compra histórica de prueba',
            'estado_compra' => $estadoCompra,
            'estado_recepcion' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $compra = Compra::query()->findOrFail($compraId);
        foreach ($items as $index => [$cantidad, $stockIngresado]) {
            $itemCreatedAt = $createdAt->copy()->addMinutes($index);
            $itemId = DB::table('compra_items')->insertGetId([
                'compra_id' => $compraId,
                'producto_id' => 1,
                'colores_id' => 1,
                'talla' => 'S',
                'cantidad' => $cantidad,
                'stock_ingresado' => $stockIngresado,
                'estado' => 'ACTIVO',
                'created_at' => $itemCreatedAt,
                'updated_at' => $itemCreatedAt,
            ]);

            $entryQuantity = $entryQuantities[$index] ?? $cantidad;
            DB::table('inventario_movimientos')->insert([
                'producto_id' => 1,
                'colores_productos_id' => 1,
                'compra_id' => $compraId,
                'user_id' => null,
                'tipo_movimiento' => 'ENTRADA_COMPRA',
                'cantidad' => $entryQuantity,
                'stock_antes' => 0,
                'stock_despues' => $entryQuantity,
                'motivo' => 'Entrada histórica de prueba',
                'metadata' => json_encode([
                    'lote' => 'test-historico',
                    'compra_item' => $itemId,
                ], JSON_THROW_ON_ERROR),
                'created_at' => $itemCreatedAt,
                'updated_at' => $itemCreatedAt,
            ]);
        }

        return $compra->fresh();
    }

    private function createMatchingHistoricalReception(Compra $compra): int
    {
        $fecha = DB::table('inventario_movimientos')
            ->where('compra_id', $compra->id)
            ->max('created_at');
        $recepcionId = DB::table('compra_recepciones')->insertGetId([
            'compra_id' => $compra->id,
            'user_id' => null,
            'fecha_recepcion' => $fecha,
            'observacion' => 'Recepción histórica normalizada; no generó movimiento de inventario.',
            'idempotency_key' => NormalizeHistoricalPurchaseReceptionsCommand::historicalIdempotencyKey((int) $compra->id),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $items = DB::table('compra_items')->where('compra_id', $compra->id)->orderBy('id')->get();
        foreach ($items as $item) {
            DB::table('compra_recepcion_items')->insert([
                'compra_recepcion_id' => $recepcionId,
                'compra_item_id' => $item->id,
                'cantidad_recibida' => $item->cantidad,
                'cantidad_aceptada' => $item->stock_ingresado,
                'cantidad_danada' => 0,
                'cantidad_incorrecta' => 0,
                'cantidad_faltante_declarada' => 0,
                'cierra_item' => true,
                'observacion' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $recepcionId;
    }

    private function inventorySnapshot(): array
    {
        return [
            'stock_ingresado' => DB::table('compra_items')->pluck('stock_ingresado', 'id')->all(),
            'movements' => DB::table('inventario_movimientos')->orderBy('id')->get()->map(
                fn (object $movement): array => [
                    'id' => (int) $movement->id,
                    'type' => $movement->tipo_movimiento,
                    'quantity' => (int) $movement->cantidad,
                    'metadata' => $movement->metadata,
                ]
            )->all(),
            'stock_variants' => DB::table('colores_productos')->pluck('stock_por_color', 'id')->all(),
            'stock_products' => DB::table('productos')->pluck('stock_venta_producto', 'id')->all(),
        ];
    }

    private function runReceptionMigrations(): void
    {
        foreach (glob(database_path('migrations/2026_10_01_1717*.php')) as $path) {
            $migration = require $path;
            $migration->up();
        }
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

        Schema::create('compras', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->string('estado_compra')->default('1');
            $table->string('estado_recepcion')->nullable();
            $table->timestamps();
        });

        Schema::create('compra_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('colores_id')->nullable();
            $table->string('talla', 50)->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('stock_ingresado')->default(0);
            $table->string('estado', 30)->default('ACTIVO');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tipo_movimiento', 40);
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_venta_producto')->default(0);
            $table->timestamps();
        });

        Schema::create('colores_productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('colores_id');
            $table->string('talla_por_color');
            $table->integer('stock_por_color')->default(0);
            $table->timestamps();
        });

        DB::table('productos')->insert([
            'id' => 1,
            'stock_venta_producto' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('colores_productos')->insert([
            'id' => 1,
            'producto_id' => 1,
            'colores_id' => 1,
            'talla_por_color' => 'S',
            'stock_por_color' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
