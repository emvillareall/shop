<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_payphone', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos_payphone', 'client_transaction_id')) {
                $table->string('client_transaction_id', 80)->nullable()->after('pago_id');
            }
            if (!Schema::hasColumn('pagos_payphone', 'payphone_id')) {
                $table->string('payphone_id', 120)->nullable()->after('client_transaction_id');
            }
            if (!Schema::hasColumn('pagos_payphone', 'status_code')) {
                $table->integer('status_code')->nullable()->after('payphone_status');
            }
            if (!Schema::hasColumn('pagos_payphone', 'transaction_status')) {
                $table->string('transaction_status', 60)->nullable()->after('status_code');
            }
            if (!Schema::hasColumn('pagos_payphone', 'raw_request_json')) {
                $table->json('raw_request_json')->nullable()->after('transaction_status');
            }
            if (!Schema::hasColumn('pagos_payphone', 'raw_response_json')) {
                $table->json('raw_response_json')->nullable()->after('raw_request_json');
            }
            if (!Schema::hasColumn('pagos_payphone', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('raw_response_json');
            }
        });

        Schema::table('pagos_payphone', function (Blueprint $table) {
            try {
                $table->unique('client_transaction_id', 'pagos_payphone_client_tx_unique');
            } catch (\Throwable) {
                // ignore if index already exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos_payphone', function (Blueprint $table) {
            if (Schema::hasColumn('pagos_payphone', 'client_transaction_id')) {
                try {
                    $table->dropUnique('pagos_payphone_client_tx_unique');
                } catch (\Throwable) {
                }
                $table->dropColumn('client_transaction_id');
            }
            foreach (['payphone_id', 'status_code', 'transaction_status', 'raw_request_json', 'raw_response_json', 'confirmed_at'] as $column) {
                if (Schema::hasColumn('pagos_payphone', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

