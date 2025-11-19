<?php

namespace App\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Resource not found',
        public readonly ?string $resourceType = null,
        public readonly ?string $resourceId = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
