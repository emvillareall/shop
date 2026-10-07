<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_recepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->dateTime('fecha_recepcion');
            $table->text('observacion')->nullable();
            $table->char('idempotency_key', 36)->nullable();
            $table->timestamps();

            $table->index(['compra_id', 'fecha_recepcion']);
            $table->unique('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_recepciones');
    }
};
