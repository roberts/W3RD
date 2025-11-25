<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Pacing;

use App\Exceptions\Game\TurnTimerExpiredException;
use App\Jobs\HandleTimerExpiredJob;
use App\Models\Games\Game;

/**
 * Asynchronous turn-based pacing.
 * Play-by-mail style with long timers (24h+). Players can close the app.
 *
 * Examples: Words with Friends, Civilization (PbEM), Diplomacy
 */
trait AsynchronousPacing
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
            throw new TurnTimerExpiredException;
        }
    }
}
