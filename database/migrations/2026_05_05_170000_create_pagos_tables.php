<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pagos')) {
            Schema::create('pagos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pedido_id')->constrained('pedidos');
                $table->string('metodo', 30); // transferencia | paypal | payphone
                $table->string('estado', 30)->default('PENDIENTE'); // PENDIENTE | EN_REVISION | APROBADO | RECHAZADO | REEMBOLSADO
                $table->decimal('monto', 12, 2)->default(0);
                $table->string('moneda', 10)->default('USD');
                $table->string('referencia_externa', 120)->nullable();
                $table->string('comprobante_path')->nullable();
                $table->text('observacion')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('revisado_por')->nullable()->constrained('users');
                $table->timestamp('revisado_at')->nullable();
                $table->timestamp('aprobado_at')->nullable();
                $table->timestamp('rechazado_at')->nullable();
                $table->timestamps();
                $table->index(['pedido_id', 'estado']);
                $table->index(['metodo', 'estado']);
            });
        }

        if (!Schema::hasTable('pagos_transferencias')) {
            Schema::create('pagos_transferencias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pago_id')->constrained('pagos');
                $table->string('banco_origen', 120)->nullable();
                $table->string('titular_origen', 120)->nullable();
                $table->string('numero_referencia', 120)->nullable();
                $table->dateTime('fecha_transferencia')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pagos_paypal')) {
            Schema::create('pagos_paypal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pago_id')->constrained('pagos');
                $table->string('paypal_order_id', 120)->nullable();
                $table->string('paypal_capture_id', 120)->nullable();
                $table->string('paypal_status', 60)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pagos_payphone')) {
            Schema::create('pagos_payphone', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pago_id')->constrained('pagos');
                $table->string('transaction_id', 120)->nullable();
                $table->string('authorization_code', 120)->nullable();
                $table->string('payphone_status', 60)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('proveedor', 30); // paypal | payphone
                $table->string('evento', 120)->nullable();
                $table->string('evento_id', 120)->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('procesado_at')->nullable();
                $table->timestamps();
                $table->index(['proveedor', 'evento_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('pagos_payphone');
        Schema::dropIfExists('pagos_paypal');
        Schema::dropIfExists('pagos_transferencias');
        Schema::dropIfExists('pagos');
    }
};

