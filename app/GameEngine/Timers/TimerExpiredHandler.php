<?php

declare(strict_types=1);

namespace App\GameEngine\Timers;

use App\GameEngine\Interfaces\GameTitleContract;
use App\Models\Games\Game;

/**
 * Handles timer expiration checking and response formatting for game actions.
 */
class TimerExpiredHandler
{
    public function checkAndHandle(Game $game, GameTitleContract $mode, object $gameState): TimerExpiredResult
    {
        // Check if there's a turn timeout
        if (! $game->turn_ends_at || now()->isBefore($game->turn_ends_at)) {
            return new TimerExpiredResult(hasExpired: false);
        }

        // Get current player ULID from game state
        $currentPlayerUlid = $gameState->currentPlayerUlid ?? null;

        if (! $currentPlayerUlid) {
            return new TimerExpiredResult(hasExpired: false);
        }

        // Handle the timer expiration using the game mode's trait method
        $outcome = $mode->handleTimerExpired($game, $gameState, $currentPlayerUlid);

        // If game is finished, create appropriate error response
        if ($outcome->isFinished) {
            $errorResponse = response()->json([
                'error' => 'Your turn has timed out. The game has ended.',
                'error_code' => 'timer_expired',
                'outcome' => $outcome->toArray(),
            ], 422);

            return new TimerExpiredResult(
                hasExpired: true,
                errorResponse: $errorResponse,
                outcome: $outcome
            );
        }

        // If turn was skipped but game continues
        $errorResponse = response()->json([
            'error' => 'Your turn has timed out.',
            'error_code' => 'timer_expired',
        ], 422);

        return new TimerExpiredResult(
            hasExpired: true,
            errorResponse: $errorResponse,
            outcome: $outcome
        );
    }
}
