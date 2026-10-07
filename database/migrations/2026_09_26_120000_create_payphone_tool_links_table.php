<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payphone_tool_links', function (Blueprint $table) {
            $table->id();
            $table->string('client_transaction_id', 15)->unique();
            $table->unsignedBigInteger('base_amount_cents');
            $table->decimal('surcharge_percent', 5, 2);
            $table->unsignedBigInteger('surcharge_amount_cents');
            $table->unsignedBigInteger('total_amount_cents');
            $table->char('currency', 3);
            $table->string('reference', 100);
            $table->text('payment_url');
            $table->string('status', 20)->default('GENERATED');
            $table->timestamp('link_expires_at')->nullable();
            $table->string('requester_ip', 64)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payphone_tool_links');
    }
};
