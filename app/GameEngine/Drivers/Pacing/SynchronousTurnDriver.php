<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Pacing;

use App\Exceptions\Game\TurnTimerExpiredException;
use App\GameEngine\Interfaces\PacingDriver;
use App\Models\Game\Game;

class SynchronousTurnDriver implements PacingDriver
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
            throw new TurnTimerExpiredException();
        }
    }
}
