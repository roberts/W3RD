<?php

declare(strict_types=1);

namespace App\Services\Timeouts;

use App\GameEngine\GameOutcome;
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
     */
    public function handleTimeout(Game $game, object $gameState, string $timedOutPlayerUlid): GameOutcome
    {
        // Game continues - controller will advance to next player
        return GameOutcome::inProgress();
    }

    /**
     * Get the handler name.
     */
    public function getName(): string
    {
        return 'pass';
    }
}
