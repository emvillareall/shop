<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ventas')) {
            return;
        }

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('clientes_id')->nullable();
            $table->unsignedBigInteger('tienda_id')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado_venta', 40)->default('BORRADOR');
            $table->timestamp('fecha_venta')->nullable();
            $table->timestamps();

            $table->index(['estado_venta', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};

