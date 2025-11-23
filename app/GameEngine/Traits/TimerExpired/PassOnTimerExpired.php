<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\TimerExpired;

use App\GameEngine\GameOutcome;
use App\Models\Games\Game;

/**
 * Pass/Skip timer expiration handling.
 * Player's turn is skipped when their timer expires.
 *
 * Examples: Turn-based games where missing a turn is acceptable
 */
trait PassOnTimerExpired
{
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome
    {
        // Skip the player's turn by advancing to next player
        $this->advanceTurn($game);
        $game->save();

        // Game continues, return in-progress outcome
        return GameOutcome::inProgress();
    }
}
