<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (! Schema::hasColumn('compras', 'estado_recepcion')) {
                $table->string('estado_recepcion', 30)->nullable()->after('estado_compra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (Schema::hasColumn('compras', 'estado_recepcion')) {
                $table->dropColumn('estado_recepcion');
            }
        });
    }
};
