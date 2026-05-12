<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ventas')) {
            return;
        }

        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'fecha_proximo_abono')) {
                $table->timestamp('fecha_proximo_abono')->nullable()->after('fecha_venta');
            }
            if (!Schema::hasColumn('ventas', 'fecha_ultimo_abono')) {
                $table->timestamp('fecha_ultimo_abono')->nullable()->after('fecha_proximo_abono');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ventas')) {
            return;
        }

        Schema::table('ventas', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('ventas', 'fecha_proximo_abono')) {
                $drop[] = 'fecha_proximo_abono';
            }
            if (Schema::hasColumn('ventas', 'fecha_ultimo_abono')) {
                $drop[] = 'fecha_ultimo_abono';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

