<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a game action is denied due to game state or rules.
 *
 * This exception is for contextual game rule violations, not parameter validation.
 * Examples: wrong turn, wrong phase, invalid move according to game rules.
 *
 * Returns HTTP 422 (Unprocessable Entity) because the request is well-formed
 * but semantically invalid for the current game state.
 *
 * Error codes come from:
 * - BaseGameActionErrorCode: Universal codes for all games
 * - Game-specific enums: Codes unique to each game (e.g., ValidateFourActionError)
 */
class GameActionDeniedException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly ?string $gameTitle = null,
        public readonly ?string $severity = 'error',
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }

    /**
     * Check if this error indicates the action might succeed if retried.
     */
    public function isRetryable(): bool
    {
        return in_array($this->errorCode, [
            'not_player_turn',
            'waiting_for_other_players',
            'action_on_cooldown',
        ]);
    }
}
