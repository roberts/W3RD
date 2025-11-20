<?php

namespace App\Exceptions;

use Exception;

class PlayerBusyException extends Exception
{
    public function __construct(
        string $message = 'Player is currently busy with another activity',
        public readonly ?string $activityType = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
