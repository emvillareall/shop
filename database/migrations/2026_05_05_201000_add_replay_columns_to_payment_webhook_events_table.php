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
            if (!Schema::hasColumn('payment_webhook_events', 'payload_hash')) {
                $table->string('payload_hash', 64)->nullable()->after('payload');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'nonce')) {
                $table->string('nonce', 191)->nullable()->after('payload_hash');
            }
            if (!Schema::hasColumn('payment_webhook_events', 'transmitido_at')) {
                $table->timestamp('transmitido_at')->nullable()->after('nonce');
            }
        });

        Schema::table('payment_webhook_events', function (Blueprint $table) {
            $table->index(['proveedor', 'payload_hash'], 'pwe_provider_hash_idx');
            $table->index(['proveedor', 'nonce'], 'pwe_provider_nonce_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_webhook_events')) {
            return;
        }

        Schema::table('payment_webhook_events', function (Blueprint $table) {
            $table->dropIndex('pwe_provider_hash_idx');
            $table->dropIndex('pwe_provider_nonce_idx');

            if (Schema::hasColumn('payment_webhook_events', 'transmitido_at')) {
                $table->dropColumn('transmitido_at');
            }
            if (Schema::hasColumn('payment_webhook_events', 'nonce')) {
                $table->dropColumn('nonce');
            }
            if (Schema::hasColumn('payment_webhook_events', 'payload_hash')) {
                $table->dropColumn('payload_hash');
            }
        });
    }
};

