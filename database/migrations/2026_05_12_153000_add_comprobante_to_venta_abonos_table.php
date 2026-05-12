<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('venta_abonos')) {
            return;
        }

        Schema::table('venta_abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_abonos', 'comprobante_path')) {
                $table->string('comprobante_path', 255)->nullable()->after('referencia');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('venta_abonos')) {
            return;
        }

        Schema::table('venta_abonos', function (Blueprint $table) {
            if (Schema::hasColumn('venta_abonos', 'comprobante_path')) {
                $table->dropColumn('comprobante_path');
            }
        });
    }
};

