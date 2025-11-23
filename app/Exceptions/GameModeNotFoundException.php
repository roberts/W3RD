<?php

namespace App\Exceptions;

use App\Enums\GameTitle;
use Exception;

class GameModeNotFoundException extends Exception
{
    public function __construct(
        string $message,
        public readonly GameTitle $gameTitle,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 500, $previous);
    }
}
