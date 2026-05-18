<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'precio_promocional')) {
                $table->decimal('precio_promocional', 12, 2)->nullable()->after('precio_venta_producto');
            }

            if (!Schema::hasColumn('productos', 'promocion_activa')) {
                $table->boolean('promocion_activa')->default(false)->after('precio_promocional');
            }

            if (!Schema::hasColumn('productos', 'promocion_fecha_inicio')) {
                $table->dateTime('promocion_fecha_inicio')->nullable()->after('promocion_activa');
            }

            if (!Schema::hasColumn('productos', 'promocion_fecha_fin')) {
                $table->dateTime('promocion_fecha_fin')->nullable()->after('promocion_fecha_inicio');
            }

            if (!Schema::hasColumn('productos', 'promocion_etiqueta')) {
                $table->string('promocion_etiqueta', 60)->nullable()->after('promocion_fecha_fin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'promocion_etiqueta',
                'promocion_fecha_fin',
                'promocion_fecha_inicio',
                'promocion_activa',
                'precio_promocional',
            ] as $column) {
                if (Schema::hasColumn('productos', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

