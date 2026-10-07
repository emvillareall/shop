<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('estado_recepcion', 30)
                ->default('EN_TRANSITO')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('estado_recepcion', 30)
                ->nullable()
                ->change();
        });
    }
};
