<?php

declare(strict_types=1);

namespace App\GameEngine\TimerExpired\Drivers;

use App\GameEngine\GameOutcome;
use App\GameEngine\TimerExpired\HandlerContract;
use App\Models\Game\Game;
use App\Models\Game\Player;

/**
 * Forfeit driver - player loses immediately when their timer expires.
 *
 * Used for competitive games where time management is critical.
 * The opponent wins automatically.
 */
class ForfeitDriver implements HandlerContract
{
    /**
     * Handle timer expiration by forfeiting the game.
     */
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

    /**
     * Get the handler name.
     */
    public function getName(): string
    {
        return 'forfeit';
    }
}
