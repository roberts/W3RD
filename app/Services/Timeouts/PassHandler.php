<?php

declare(strict_types=1);

namespace App\Services\Timeouts;

use App\Games\GameOutcome;
use App\Models\Game\Game;

/**
 * Pass handler - turn is skipped on timeout.
 *
 * Used for games where missing a turn is a sufficient penalty.
 * Game continues with the next player.
 */
class PassHandler implements TimeoutHandlerContract
{
    /**
     * Handle timeout by passing the turn.
     *
     * The game state is advanced to the next player without any action.
     *
     * @param Game $game
     * @param object $gameState
     * @param string $timedOutPlayerUlid
     * @return GameOutcome
     */
    public function handleTimeout(Game $game, object $gameState, string $timedOutPlayerUlid): GameOutcome
    {
        // Game continues - controller will advance to next player
        return GameOutcome::inProgress();
    }

    /**
     * Get the handler name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'pass';
    }
}
