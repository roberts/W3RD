<?php

namespace App\Exceptions;

use Exception;

class LobbyStateException extends Exception
{
    public function __construct(
        string $message = 'Invalid lobby state for this operation',
        public readonly ?string $currentState = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
