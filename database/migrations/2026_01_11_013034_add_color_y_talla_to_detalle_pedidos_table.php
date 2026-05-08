<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (! Schema::hasColumn('detalle_pedidos', 'id_color_producto')) {
                $table->integer('id_color_producto')->nullable()->after('pedido_id');
            }

            if (! Schema::hasColumn('detalle_pedidos', 'talla_por_color')) {
                $table->string('talla_por_color', 50)->nullable()->after('id_color_producto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_pedidos', 'talla_por_color')) {
                $table->dropColumn('talla_por_color');
            }

            if (Schema::hasColumn('detalle_pedidos', 'id_color_producto')) {
                $table->dropColumn('id_color_producto');
            }
        });
    }
};
