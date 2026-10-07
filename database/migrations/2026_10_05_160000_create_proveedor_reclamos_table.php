<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_reclamos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_id')
                ->constrained('compras')
                ->restrictOnDelete();
            $table->foreignId('creado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('estado', 30)->default('BORRADOR');
            $table->string('referencia_proveedor')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('compra_id', 'proveedor_reclamos_compra_id_idx');
            $table->index('estado', 'proveedor_reclamos_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_reclamos');
    }
};
