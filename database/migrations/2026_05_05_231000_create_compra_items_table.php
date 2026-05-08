<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compra_items')) {
            Schema::create('compra_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compra_id')->constrained('compras');
                $table->foreignId('producto_id')->nullable()->constrained('productos');
                $table->unsignedBigInteger('colores_id')->nullable();
                $table->string('talla', 50)->nullable();
                $table->integer('cantidad')->default(0);
                $table->integer('stock_ingresado')->default(0);
                $table->decimal('costo_unitario_pesos', 12, 2)->default(0);
                $table->decimal('costo_unitario_dolares', 12, 2)->default(0);
                $table->decimal('subtotal_pesos', 12, 2)->default(0);
                $table->decimal('subtotal_dolares', 12, 2)->default(0);
                $table->string('estado', 30)->default('ACTIVO');
                $table->text('observacion')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['compra_id', 'producto_id']);
                $table->index(['compra_id', 'colores_id', 'talla']);
            });
        }

        // Backfill minimo para no dejar compras antiguas sin items.
        $legacyProductos = DB::table('productos')
            ->whereNotNull('compras_id')
            ->select('id', 'compras_id', 'cantidad_compra_producto', 'precio_pesos_producto', 'precio_dolares_producto')
            ->get();

        foreach ($legacyProductos as $producto) {
            $exists = DB::table('compra_items')
                ->where('compra_id', $producto->compras_id)
                ->where('producto_id', $producto->id)
                ->whereNull('colores_id')
                ->whereNull('talla')
                ->exists();

            if ($exists) {
                continue;
            }

            $cantidad = (int) ($producto->cantidad_compra_producto ?? 0);
            $ppu = (float) ($producto->precio_pesos_producto ?? 0);
            $pdu = (float) ($producto->precio_dolares_producto ?? 0);

            DB::table('compra_items')->insert([
                'compra_id' => (int) $producto->compras_id,
                'producto_id' => (int) $producto->id,
                'cantidad' => $cantidad,
                'stock_ingresado' => $cantidad,
                'costo_unitario_pesos' => $ppu,
                'costo_unitario_dolares' => $pdu,
                'subtotal_pesos' => $cantidad * $ppu,
                'subtotal_dolares' => $cantidad * $pdu,
                'estado' => 'ACTIVO',
                'observacion' => 'Backfill inicial de compras legacy',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_items');
    }
};

