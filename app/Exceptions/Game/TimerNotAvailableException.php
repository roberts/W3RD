<?php

namespace App\Exceptions\Game;

use Exception;

class TimerNotAvailableException extends Exception
{
    public function __construct(string $message = 'This game does not have a timer')
    {
        parent::__construct($message, 400);
    }

    public static function noTimer(): self
    {
        return new self('This game does not use a timer');
    }
}
