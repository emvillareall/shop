<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lineas_ropa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_linea')->unique();
            $table->string('estado_linea')->default('1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_ropa');
    }
};
