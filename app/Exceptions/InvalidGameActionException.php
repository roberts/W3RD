<?php

namespace App\Exceptions;

use Exception;

class InvalidGameActionException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $actionType,
        public readonly array $actionDetails,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}
