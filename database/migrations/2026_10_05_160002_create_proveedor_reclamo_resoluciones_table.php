<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_reclamo_resoluciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proveedor_reclamo_item_id')
                ->constrained('proveedor_reclamo_items')
                ->restrictOnDelete();
            $table->string('tipo_resolucion', 35);
            $table->unsignedInteger('cantidad');
            $table->string('estado', 30)->default('PENDIENTE');
            $table->string('referencia')->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('resuelto_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->decimal('importe', 12, 2)->nullable();
            $table->string('moneda', 3)->nullable();
            $table->timestamps();

            $table->index('proveedor_reclamo_item_id', 'proveedor_reclamo_resoluciones_item_id_idx');
            $table->index('tipo_resolucion', 'proveedor_reclamo_resoluciones_tipo_idx');
            $table->index('estado', 'proveedor_reclamo_resoluciones_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_reclamo_resoluciones');
    }
};
