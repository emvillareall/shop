<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30)->unique();
            $table->string('environment', 20)->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->string('base_url')->nullable();
            $table->string('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('merchant_id', 120)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('webhook_id', 180)->nullable();
            $table->string('webhook_secret', 200)->nullable();
            $table->string('verify_path', 120)->nullable();
            $table->boolean('strict_webhook')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};

