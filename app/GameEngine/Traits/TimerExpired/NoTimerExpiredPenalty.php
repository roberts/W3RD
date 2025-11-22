<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\TimerExpired;

use App\GameEngine\GameOutcome;
use App\Models\Game\Game;

/**
 * No timer expiration handling.
 * Timer is informational only, no penalty applied.
 * 
 * Examples: Casual games, practice modes
 */
trait NoTimerExpiredPenalty
{
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome
    {
        // No penalty applied, game continues
        return GameOutcome::inProgress();
    }
}
