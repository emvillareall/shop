<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_compra');
            $table->string('descripcion_compra');
            $table->double('envio_compra', 8, 2)->default(0);
            $table->double('total_pesos_compra', 8, 2)->default(0);
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->string('estado_compra')->default(1);
            $table->double('total_dolares_compra', 8, 2)->default(0);
            $table->double('importacion_compra', 8, 2)->default(0);
            $table->double('total_final_compra', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
