<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_reclamo_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proveedor_reclamo_id');
            $table->foreignId('proveedor_reclamo_item_id')->nullable();
            $table->foreignId('proveedor_reclamo_resolucion_id')->nullable();
            $table->string('accion', 80);
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30)->nullable();
            $table->foreignId('usuario_id')->nullable();
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_evento')->useCurrent();
            $table->timestamps();

            $table->foreign('proveedor_reclamo_id', 'proveedor_reclamo_eventos_reclamo_fk')
                ->references('id')
                ->on('proveedor_reclamos')
                ->restrictOnDelete();
            $table->foreign('proveedor_reclamo_item_id', 'proveedor_reclamo_eventos_item_fk')
                ->references('id')
                ->on('proveedor_reclamo_items')
                ->restrictOnDelete();
            $table->foreign('proveedor_reclamo_resolucion_id', 'proveedor_reclamo_eventos_resolucion_fk')
                ->references('id')
                ->on('proveedor_reclamo_resoluciones')
                ->restrictOnDelete();
            $table->foreign('usuario_id', 'proveedor_reclamo_eventos_usuario_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(
                ['proveedor_reclamo_id', 'fecha_evento'],
                'proveedor_reclamo_eventos_reclamo_fecha_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_reclamo_eventos');
    }
};
