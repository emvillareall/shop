<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'codigo_pedido')) {
                $table->string('codigo_pedido', 40)->nullable()->after('id');
                $table->index('codigo_pedido');
            }

            if (!Schema::hasColumn('pedidos', 'estado_pedido')) {
                $table->string('estado_pedido', 40)->default('PENDIENTE_PAGO')->after('estado_url');
                $table->index('estado_pedido');
            }

            if (!Schema::hasColumn('pedidos', 'estado_pago')) {
                $table->string('estado_pago', 40)->default('SIN_PAGO')->after('estado_pedido');
                $table->index('estado_pago');
            }

            if (!Schema::hasColumn('pedidos', 'estado_envio')) {
                $table->string('estado_envio', 40)->default('SIN_ENVIO')->after('estado_pago');
                $table->index('estado_envio');
            }

            if (!Schema::hasColumn('pedidos', 'confirmado_at')) {
                $table->timestamp('confirmado_at')->nullable()->after('estado_envio');
            }

            if (!Schema::hasColumn('pedidos', 'pagado_at')) {
                $table->timestamp('pagado_at')->nullable()->after('confirmado_at');
            }

            if (!Schema::hasColumn('pedidos', 'despachado_at')) {
                $table->timestamp('despachado_at')->nullable()->after('pagado_at');
            }

            if (!Schema::hasColumn('pedidos', 'cancelado_at')) {
                $table->timestamp('cancelado_at')->nullable()->after('despachado_at');
            }
        });

        // Backfill compatible para pedidos existentes
        DB::table('pedidos')
            ->whereNull('estado_pedido')
            ->orWhere('estado_pedido', '')
            ->update(['estado_pedido' => 'PENDIENTE_PAGO']);

        DB::table('pedidos')
            ->whereNull('estado_pago')
            ->orWhere('estado_pago', '')
            ->update(['estado_pago' => 'SIN_PAGO']);

        DB::table('pedidos')
            ->whereNull('estado_envio')
            ->orWhere('estado_envio', '')
            ->update(['estado_envio' => 'SIN_ENVIO']);

        DB::table('pedidos')
            ->where('estado_url', 'ENVIADO')
            ->update([
                'estado_pedido' => 'DESPACHADO',
                'estado_envio' => 'ENVIADO',
            ]);

        DB::table('pedidos')
            ->where('estado_pedidos', 0)
            ->update([
                'estado_pedido' => 'CANCELADO',
                'cancelado_at' => DB::raw('COALESCE(cancelado_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos', 'cancelado_at')) {
                $table->dropColumn('cancelado_at');
            }
            if (Schema::hasColumn('pedidos', 'despachado_at')) {
                $table->dropColumn('despachado_at');
            }
            if (Schema::hasColumn('pedidos', 'pagado_at')) {
                $table->dropColumn('pagado_at');
            }
            if (Schema::hasColumn('pedidos', 'confirmado_at')) {
                $table->dropColumn('confirmado_at');
            }
            if (Schema::hasColumn('pedidos', 'estado_envio')) {
                $table->dropIndex(['estado_envio']);
                $table->dropColumn('estado_envio');
            }
            if (Schema::hasColumn('pedidos', 'estado_pago')) {
                $table->dropIndex(['estado_pago']);
                $table->dropColumn('estado_pago');
            }
            if (Schema::hasColumn('pedidos', 'estado_pedido')) {
                $table->dropIndex(['estado_pedido']);
                $table->dropColumn('estado_pedido');
            }
            if (Schema::hasColumn('pedidos', 'codigo_pedido')) {
                $table->dropIndex(['codigo_pedido']);
                $table->dropColumn('codigo_pedido');
            }
        });
    }
};

