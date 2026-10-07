<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->boolean('publicado_ecommerce')
                ->default(false)
                ->after('estado_producto');
        });

        // Preserve the current ecommerce visibility before PUBLICACION-1C
        // introduces the independent publication predicate.
        DB::table('productos')
            ->where('estado_producto', 1)
            ->update(['publicado_ecommerce' => true]);
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropColumn('publicado_ecommerce');
        });
    }
};
