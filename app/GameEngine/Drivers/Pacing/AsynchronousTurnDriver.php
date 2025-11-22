<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Pacing;

use App\Exceptions\Game\TurnTimerExpiredException;
use App\GameEngine\Interfaces\PacingDriver;
use App\Jobs\HandleTimerExpiredJob;
use App\Models\Game\Game;

class AsynchronousTurnDriver implements PacingDriver
{
    public function startTurnTimer(Game $game): void
    {
        // Logic to set a long timer, e.g., 24 hours
        $timeout = now()->addHours(24);
        $game->turn_ends_at = $timeout;
        $game->save();

        // Dispatch a job to handle the timeout
        HandleTimerExpiredJob::dispatch($game)->delay($timeout);
    }

    public function validateActionTime(Game $game): void
    {
        if ($game->turn_ends_at && now()->isAfter($game->turn_ends_at)) {
            throw new TurnTimerExpiredException();
        }
    }
}
