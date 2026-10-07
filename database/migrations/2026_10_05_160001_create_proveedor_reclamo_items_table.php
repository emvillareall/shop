<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_reclamo_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proveedor_reclamo_id')
                ->constrained('proveedor_reclamos')
                ->restrictOnDelete();
            $table->foreignId('compra_recepcion_item_id')
                ->constrained('compra_recepcion_items')
                ->restrictOnDelete();
            $table->string('tipo_incidencia', 20);
            $table->unsignedInteger('cantidad_reclamada');
            $table->string('estado', 30)->default('PENDIENTE');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('proveedor_reclamo_id', 'proveedor_reclamo_items_reclamo_id_idx');
            $table->index('compra_recepcion_item_id', 'proveedor_reclamo_items_recepcion_item_id_idx');
            $table->index('tipo_incidencia', 'proveedor_reclamo_items_tipo_idx');
            $table->index('estado', 'proveedor_reclamo_items_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_reclamo_items');
    }
};
