<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\TimerExpired;

use App\GameEngine\GameOutcome;
use App\Models\Games\Game;
use App\Models\Games\Player;

/**
 * Forfeit timer expiration handling.
 * Player loses immediately when their timer expires.
 *
 * Examples: Chess, Checkers, competitive games where time is critical
 */
trait ForfeitOnTimerExpired
{
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome
    {
        // Find the opponent (winner)
        /** @var Player|null $winnerPlayer */
        $winnerPlayer = $game->players()
            ->where('ulid', '!=', $playerUlid)
            ->first();

        if (! $winnerPlayer) {
            // Fallback if no opponent found (shouldn't happen)
            return GameOutcome::draw('timer_expired_no_opponent');
        }

        return GameOutcome::win($winnerPlayer->ulid, null, 'timer_expired_forfeit');
    }
}
