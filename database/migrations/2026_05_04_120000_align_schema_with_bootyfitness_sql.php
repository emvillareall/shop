<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_productos', function (Blueprint $table) {
            if (Schema::hasIndex('categorias_productos', 'categorias_productos_nombre_categoria_unique')) {
                $table->dropUnique('categorias_productos_nombre_categoria_unique');
            }
        });

        Schema::table('colores_productos', function (Blueprint $table) {
            if (Schema::hasIndex('colores_productos', 'cp_prod_color_talla_idx')) {
                $table->dropIndex('cp_prod_color_talla_idx');
            }
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasIndex('detalle_pedidos', 'dp_pedido_producto_idx')) {
                $table->dropIndex('dp_pedido_producto_idx');
            }

            if (Schema::hasIndex('detalle_pedidos', 'dp_variante_idx')) {
                $table->dropIndex('dp_variante_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categorias_productos', function (Blueprint $table) {
            if (! Schema::hasIndex('categorias_productos', 'categorias_productos_nombre_categoria_unique')) {
                $table->unique('nombre_categoria', 'categorias_productos_nombre_categoria_unique');
            }
        });

        Schema::table('colores_productos', function (Blueprint $table) {
            if (! Schema::hasIndex('colores_productos', 'cp_prod_color_talla_idx')) {
                $table->index(['producto_id', 'colores_id', 'talla_por_color'], 'cp_prod_color_talla_idx');
            }
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (! Schema::hasIndex('detalle_pedidos', 'dp_pedido_producto_idx')) {
                $table->index(['pedido_id', 'producto_id'], 'dp_pedido_producto_idx');
            }

            if (! Schema::hasIndex('detalle_pedidos', 'dp_variante_idx')) {
                $table->index(['producto_id', 'id_color_producto', 'talla_por_color'], 'dp_variante_idx');
            }
        });
    }
};
