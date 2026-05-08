<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservas', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 120)->index();
            $table->unsignedBigInteger('producto_id')->index();
            $table->unsignedBigInteger('color_id')->index();
            $table->string('talla', 40)->index();
            $table->unsignedInteger('cantidad');
            $table->timestamp('expires_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'producto_id', 'color_id', 'talla'], 'uniq_reserva_session_variante');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservas');
    }
};

