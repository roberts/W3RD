<?php

declare(strict_types=1);

namespace App\Services\Timeouts;

use App\Games\GameOutcome;
use App\Models\Game\Game;

/**
 * Forfeit handler - player loses immediately on timeout.
 *
 * Used for competitive games where time management is critical.
 * The opponent wins automatically.
 */
class ForfeitHandler implements TimeoutHandlerContract
{
    /**
     * Handle timeout by forfeiting the game.
     *
     * @param Game $game
     * @param object $gameState
     * @param string $timedOutPlayerUlid
     * @return GameOutcome
     */
    public function handleTimeout(Game $game, object $gameState, string $timedOutPlayerUlid): GameOutcome
    {
        // Find the opponent (winner)
        $winnerPlayer = $game->players()
            ->where('ulid', '!=', $timedOutPlayerUlid)
            ->first();

        if (!$winnerPlayer) {
            // Fallback if no opponent found (shouldn't happen)
            return GameOutcome::draw('timeout_no_opponent');
        }

        return GameOutcome::win($winnerPlayer->ulid, 'timeout_forfeit');
    }

    /**
     * Get the handler name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'forfeit';
    }
}
