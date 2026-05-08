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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres_clientes');
            $table->string('apellidos_clientes');
            $table->string('cedula_clientes')->unique();
            $table->string('telefono_clientes');
            $table->string('ciudad_clientes');
            $table->string('direccion_clientes');
            $table->string('email_clientes')->unique()->nullable();
            $table->string('estado_clientes')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
