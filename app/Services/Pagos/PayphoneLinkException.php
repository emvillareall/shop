<?php

namespace App\Services\Pagos;

use RuntimeException;

class PayphoneLinkException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 503,
        public readonly string $errorCode = 'payphone_link_error',
        public readonly array $providerError = []
    ) {
        parent::__construct($message);
    }
}
