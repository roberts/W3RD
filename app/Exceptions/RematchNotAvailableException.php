<?php

namespace App\Exceptions;

use Exception;

class RematchNotAvailableException extends Exception
{
    protected $message = 'Opponent not available for rematch';

    /** @var int */
    protected $code = 422;

    public function __construct(?string $message = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $this->code);
    }
}
