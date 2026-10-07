<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedor_reclamo_reposiciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('proveedor_reclamo_resolucion_id');
            $table->unsignedBigInteger('recibido_por');
            $table->char('idempotency_key', 36);
            $table->unsignedInteger('cantidad_recibida');
            $table->unsignedInteger('cantidad_aceptada')->default(0);
            $table->unsignedInteger('cantidad_danada')->default(0);
            $table->unsignedInteger('cantidad_incorrecta')->default(0);
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_recepcion');
            $table->timestamps();

            $table->unique('idempotency_key', 'proveedor_reclamo_reposiciones_idempotency_unique');
            $table->index(
                ['proveedor_reclamo_resolucion_id', 'fecha_recepcion'],
                'proveedor_reclamo_reposiciones_resolucion_fecha_idx'
            );
            $table->foreign('proveedor_reclamo_resolucion_id', 'prr_reposiciones_resolucion_fk')
                ->references('id')
                ->on('proveedor_reclamo_resoluciones')
                ->restrictOnDelete();
            $table->foreign('recibido_por', 'prr_reposiciones_recibido_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('inventario_movimientos', function (Blueprint $table): void {
            $table->unsignedBigInteger('proveedor_reclamo_reposicion_id')
                ->nullable()
                ->after('compra_recepcion_item_id');
            $table->foreign('proveedor_reclamo_reposicion_id', 'im_reposicion_proveedor_fk')
                ->references('id')
                ->on('proveedor_reclamo_reposiciones')
                ->restrictOnDelete();
            $table->index(
                'proveedor_reclamo_reposicion_id',
                'inventario_movimientos_reposicion_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['proveedor_reclamo_reposicion_id']);
            } else {
                $table->dropForeign('im_reposicion_proveedor_fk');
            }
            $table->dropIndex('inventario_movimientos_reposicion_id_idx');
            $table->dropColumn('proveedor_reclamo_reposicion_id');
        });

        Schema::dropIfExists('proveedor_reclamo_reposiciones');
    }
};
