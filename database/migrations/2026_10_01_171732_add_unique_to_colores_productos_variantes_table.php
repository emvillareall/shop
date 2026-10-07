<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'colores_productos_producto_color_talla_unique';

    public function up(): void
    {
        $column = collect(Schema::getColumns('colores_productos'))
            ->firstWhere('name', 'talla_por_color');

        // A UNIQUE index in MySQL allows multiple NULL values. Skip it when
        // the variant key is nullable because it would not guarantee the
        // expected uniqueness for this phase.
        if (! $column || (bool) ($column['nullable'] ?? false)) {
            return;
        }

        $duplicates = DB::table('colores_productos')
            ->select('producto_id', 'colores_id', 'talla_por_color')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('producto_id', 'colores_id', 'talla_por_color')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'No se puede crear el UNIQUE de variantes: existen duplicados en producto_id, colores_id, talla_por_color.'
            );
        }

        if (! Schema::hasIndex('colores_productos', self::INDEX_NAME)) {
            Schema::table('colores_productos', function (Blueprint $table) {
                $table->unique(
                    ['producto_id', 'colores_id', 'talla_por_color'],
                    self::INDEX_NAME
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('colores_productos', self::INDEX_NAME)) {
            Schema::table('colores_productos', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }
    }
};
