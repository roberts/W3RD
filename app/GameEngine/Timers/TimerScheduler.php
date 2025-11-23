<?php

declare(strict_types=1);

namespace App\GameEngine\Timers;

use App\Enums\GameAttributes\GamePacing;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Jobs\TimerExpiredJob;
use App\Models\Games\Game;

/**
 * Schedules timer expiration jobs for game turns.
 */
class TimerScheduler
{
    /**
     * Schedule a timer expiration job for the next player's turn.
     */
    public function scheduleForNextPlayer(Game $game, GameTitleContract $mode): void
    {
        $pacing = $mode->getPacing();

        $delay = match ($pacing) {
            GamePacing::TURN_BASED_ASYNC => now()->addMinutes(5), // Relaxed
            GamePacing::TURN_BASED_SYNC => now()->addSeconds(60), // Standard
            GamePacing::REALTIME => now()->addSeconds(15), // Blitz/Realtime
            default => null,
        };

        if ($delay && $game->currentPlayer()) {
            TimerExpiredJob::dispatch(
                $game->id,
                $game->currentPlayer()->id,
                $game->turn_number
            )->delay($delay);
        }
    }
}
