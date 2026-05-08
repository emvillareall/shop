<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colores_productos', function (Blueprint $table) {
            if (! Schema::hasColumn('colores_productos', 'talla_por_color')) {
                $table->string('talla_por_color')->after('stock_por_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('colores_productos', function (Blueprint $table) {
            if (Schema::hasColumn('colores_productos', 'talla_por_color')) {
                $table->dropColumn('talla_por_color');
            }
        });
    }
};
