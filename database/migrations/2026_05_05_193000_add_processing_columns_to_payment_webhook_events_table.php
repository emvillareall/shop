<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_webhook_events')) {
            return;
        }

        Schema::table('payment_webhook_events', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_webhook_events', 'firma_valida')) {
                $table->boolean('firma_valida')->default(false)->after('payload');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'procesado')) {
                $table->boolean('procesado')->default(false)->after('firma_valida');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'intentos')) {
                $table->unsignedSmallInteger('intentos')->default(0)->after('procesado');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'error')) {
                $table->text('error')->nullable()->after('intentos');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'pago_id')) {
                $table->foreignId('pago_id')->nullable()->after('error')->constrained('pagos');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_webhook_events')) {
            return;
        }

        Schema::table('payment_webhook_events', function (Blueprint $table) {
            if (Schema::hasColumn('payment_webhook_events', 'pago_id')) {
                $table->dropConstrainedForeignId('pago_id');
            }
            if (Schema::hasColumn('payment_webhook_events', 'error')) {
                $table->dropColumn('error');
            }
            if (Schema::hasColumn('payment_webhook_events', 'intentos')) {
                $table->dropColumn('intentos');
            }
            if (Schema::hasColumn('payment_webhook_events', 'procesado')) {
                $table->dropColumn('procesado');
            }
            if (Schema::hasColumn('payment_webhook_events', 'firma_valida')) {
                $table->dropColumn('firma_valida');
            }
        });
    }
};

