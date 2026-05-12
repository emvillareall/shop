<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayConfig extends Model
{
    protected $fillable = [
        'gateway',
        'environment',
        'is_active',
        'base_url',
        'public_key',
        'secret_key',
        'merchant_id',
        'currency',
        'webhook_id',
        'webhook_secret',
        'verify_path',
        'strict_webhook',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'strict_webhook' => 'boolean',
        'settings' => 'array',
    ];

    public function getSecretKeyAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setSecretKeyAttribute(?string $value): void
    {
        $this->attributes['secret_key'] = $value ? Crypt::encryptString($value) : null;
    }
}

