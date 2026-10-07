<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('compra_item_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_id')->nullable();
            $table->unsignedBigInteger('compra_recepcion_item_id')->nullable();

            $table->index('compra_item_id');
            $table->index('compra_recepcion_id');
            $table->index('compra_recepcion_item_id');

            $table->foreign('compra_item_id')
                ->references('id')
                ->on('compra_items');
            $table->foreign('compra_recepcion_id')
                ->references('id')
                ->on('compra_recepciones');
            $table->foreign('compra_recepcion_item_id')
                ->references('id')
                ->on('compra_recepcion_items');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropForeign(['compra_item_id']);
            $table->dropForeign(['compra_recepcion_id']);
            $table->dropForeign(['compra_recepcion_item_id']);

            $table->dropIndex(['compra_item_id']);
            $table->dropIndex(['compra_recepcion_id']);
            $table->dropIndex(['compra_recepcion_item_id']);

            $table->dropColumn([
                'compra_item_id',
                'compra_recepcion_id',
                'compra_recepcion_item_id',
            ]);
        });
    }
};
