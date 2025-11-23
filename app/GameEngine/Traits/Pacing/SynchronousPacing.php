<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Pacing;

use App\Exceptions\Game\TurnTimerExpiredException;
use App\Models\Games\Game;

/**
 * Synchronous turn-based pacing.
 * Players stay online with short timers (15s - 5m).
 *
 * Examples: Hearthstone, Speed Chess (Blitz), Uno
 */
trait SynchronousPacing
{
    public function startTurnTimer(Game $game): void
    {
        // Logic to set a short timer, e.g., 60 seconds
        $game->turn_ends_at = now()->addSeconds(60);
        $game->save();
    }

    public function validateActionTime(Game $game): void
    {
        if ($game->turn_ends_at && now()->isAfter($game->turn_ends_at)) {
            throw new TurnTimerExpiredException;
        }
    }
}
