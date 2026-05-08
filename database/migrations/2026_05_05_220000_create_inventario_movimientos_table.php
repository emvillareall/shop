<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventario_movimientos')) {
            return;
        }

        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedBigInteger('colores_productos_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('tipo_movimiento', 40); // ENTRADA_COMPRA|SALIDA_VENTA|AJUSTE|REVERSO
            $table->integer('cantidad');
            $table->integer('stock_antes')->nullable();
            $table->integer('stock_despues')->nullable();
            $table->string('motivo', 180)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'created_at']);
            $table->index(['tipo_movimiento', 'created_at']);
            $table->index(['pedido_id', 'compra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};

