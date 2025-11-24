<?php

namespace App\Exceptions;

use Exception;

class InvalidGameConfigurationException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'Invalid game configuration',
        public readonly ?string $gameTitle = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
