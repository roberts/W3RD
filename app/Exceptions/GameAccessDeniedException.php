<?php

namespace App\Exceptions;

use Exception;

class GameAccessDeniedException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'You do not have access to this game resource',
        public readonly ?string $gameUlid = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
