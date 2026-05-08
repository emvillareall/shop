<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_tienda');
            $table->string('nombres_dueno_tienda');
            $table->string('apellidos_dueno_tienda');
            $table->string('cedula_dueno_tienda');
            $table->string('telefono_dueno_tienda');
            $table->string('direccion_dueno_tienda');
            $table->string('email_dueno_tienda')->unique()->nullable();
            $table->string('estado_tienda')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiendas');
    }
};
