<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_pedidos', 'nombre_producto_snapshot')) {
                $table->string('nombre_producto_snapshot')->nullable()->after('producto_id');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'color_snapshot')) {
                $table->string('color_snapshot', 120)->nullable()->after('id_color_producto');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'talla_snapshot')) {
                $table->string('talla_snapshot', 50)->nullable()->after('talla_por_color');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'precio_unitario_snapshot')) {
                $table->decimal('precio_unitario_snapshot', 12, 2)->nullable()->after('talla_snapshot');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'subtotal_linea')) {
                $table->decimal('subtotal_linea', 12, 2)->nullable()->after('precio_unitario_snapshot');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'descuento_linea')) {
                $table->decimal('descuento_linea', 12, 2)->default(0)->after('subtotal_linea');
            }
            if (!Schema::hasColumn('detalle_pedidos', 'impuesto_linea')) {
                $table->decimal('impuesto_linea', 12, 2)->default(0)->after('descuento_linea');
            }
        });

        DB::statement("
            UPDATE detalle_pedidos dp
            LEFT JOIN productos p ON p.id = dp.producto_id
            LEFT JOIN colores c ON c.id = dp.id_color_producto
            SET
                dp.nombre_producto_snapshot = COALESCE(dp.nombre_producto_snapshot, p.descripcion_producto),
                dp.color_snapshot = COALESCE(dp.color_snapshot, c.nombre_color),
                dp.talla_snapshot = COALESCE(dp.talla_snapshot, dp.talla_por_color),
                dp.precio_unitario_snapshot = COALESCE(dp.precio_unitario_snapshot, p.precio_venta_producto),
                dp.subtotal_linea = COALESCE(dp.subtotal_linea, (COALESCE(p.precio_venta_producto, 0) * COALESCE(dp.cantidad_producto, 0))),
                dp.descuento_linea = COALESCE(dp.descuento_linea, 0),
                dp.impuesto_linea = COALESCE(dp.impuesto_linea, 0)
        ");
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            foreach ([
                'impuesto_linea',
                'descuento_linea',
                'subtotal_linea',
                'precio_unitario_snapshot',
                'talla_snapshot',
                'color_snapshot',
                'nombre_producto_snapshot',
            ] as $column) {
                if (Schema::hasColumn('detalle_pedidos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

