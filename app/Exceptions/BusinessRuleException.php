<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    public function __construct(
        private string $errorCode,
        string $message,
        private int $statusCode = 409
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Factory methods for common errors
    public static function insufficientBalance(string $currency): self
    {
        return new self(
            'INSUFFICIENT_BALANCE',
            "Not enough {$currency} to complete this operation"
        );
    }

    public static function notYourTurn(): self
    {
        return new self(
            'TURN_NOT_YOURS',
            'It is not your turn to play'
        );
    }

    public static function lobbyFull(): self
    {
        return new self(
            'LOBBY_FULL',
            'This lobby has reached maximum capacity'
        );
    }

    public static function maxProposalsExceeded(): self
    {
        return new self(
            'MAX_PROPOSALS_EXCEEDED',
            'You have reached the maximum number of active proposals',
            429
        );
    }

    public static function tournamentRegistrationClosed(): self
    {
        return new self(
            'TOURNAMENT_REGISTRATION_CLOSED',
            'Tournament registration is closed'
        );
    }

    public static function alreadyInTournament(): self
    {
        return new self(
            'ALREADY_IN_TOURNAMENT',
            'You are already registered for this tournament'
        );
    }
}
