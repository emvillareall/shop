<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->double('envio_compra', 8, 2)->default(0)->change();
            $table->double('total_pesos_compra', 8, 2)->default(0)->change();
            $table->double('total_dolares_compra', 8, 2)->default(0)->change();
            $table->double('importacion_compra', 8, 2)->default(0)->change();
            $table->double('total_final_compra', 8, 2)->default(0)->change();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->double('precio_pesos_producto', 8, 2)->default(0)->change();
            $table->double('precio_dolares_producto', 8, 2)->default(0)->change();
            $table->double('precio_venta_producto', 8, 2)->default(0)->change();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->double('subtotal_pedido', 8, 2)->nullable()->default(0)->change();
            $table->double('iva_pedido', 8, 2)->nullable()->default(0)->change();
            $table->double('descuentos_pedido', 8, 2)->nullable()->default(0)->change();
            $table->double('total_pedido', 8, 2)->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        // No-op: we preserve precision alignment for financial columns.
    }
};
