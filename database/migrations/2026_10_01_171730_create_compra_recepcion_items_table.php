<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_recepcion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_recepcion_id')->constrained('compra_recepciones');
            $table->foreignId('compra_item_id')->constrained('compra_items');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->unsignedInteger('cantidad_aceptada')->default(0);
            $table->unsignedInteger('cantidad_danada')->default(0);
            $table->unsignedInteger('cantidad_incorrecta')->default(0);
            $table->unsignedInteger('cantidad_faltante_declarada')->default(0);
            $table->boolean('cierra_item')->default(false);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['compra_recepcion_id', 'compra_item_id']);
            $table->index(['compra_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_recepcion_items');
    }
};
