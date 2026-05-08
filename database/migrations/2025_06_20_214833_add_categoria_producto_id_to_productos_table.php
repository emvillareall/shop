<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('categoria_producto_id')
                  ->nullable()
                  ->constrained('categorias_productos')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_producto_id']);
            $table->dropColumn('categoria_producto_id');
        });
    }
};
