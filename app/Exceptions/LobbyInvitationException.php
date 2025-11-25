<?php

namespace App\Exceptions;

use Exception;

class LobbyInvitationException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'Invalid lobby invitation operation',
        public readonly ?string $invitationStatus = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
