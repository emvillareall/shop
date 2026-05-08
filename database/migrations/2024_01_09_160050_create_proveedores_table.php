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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('tienda_proveedor');
            $table->string('nombres_proveedor');
            $table->string('apellidos_proveedor');
            $table->string('cedula_proveedor')->unique()->nullable();
            $table->string('telefono_proveedor')->nullable();
            $table->string('direccion_proveedor')->nullable();
            $table->string('email_proveedor')->unique()->nullable();
            $table->string('estado_proveedor')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
