<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ventas') && !Schema::hasColumn('ventas', 'modalidad_venta')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->string('modalidad_venta', 20)->default('CONTADO')->after('estado_venta');
            });
        }

        if (!Schema::hasTable('venta_abonos')) {
            Schema::create('venta_abonos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venta_id');
                $table->unsignedBigInteger('pedido_id');
                $table->decimal('monto', 12, 2);
                $table->string('metodo', 30)->default('efectivo');
                $table->string('referencia', 120)->nullable();
                $table->text('observacion')->nullable();
                $table->timestamp('fecha_abono')->useCurrent();
                $table->unsignedBigInteger('registrado_por')->nullable();
                $table->timestamps();

                $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
                $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('cascade');
                $table->index(['pedido_id', 'fecha_abono']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('venta_abonos')) {
            Schema::dropIfExists('venta_abonos');
        }

        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'modalidad_venta')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('modalidad_venta');
            });
        }
    }
};

