<?php

namespace App\Exceptions;

use Exception;

class PaymentValidationException extends Exception
{
    public function __construct(
        string $message = 'Payment validation failed',
        public readonly ?string $provider = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
