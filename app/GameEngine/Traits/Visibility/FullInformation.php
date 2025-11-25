<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Visibility;

use App\Models\Auth\User;

/**
 * Full information visibility.
 * All players see the complete, identical game state.
 *
 * Examples: Chess, Checkers, Connect Four, Go
 */
trait FullInformation
{
    public function redact(object $gameState, User $player): object
    {
        // Return the full state, no redaction needed.
        return $gameState;
    }
}
