<?php

declare(strict_types=1);

namespace App\Services\Timeouts;

use App\Games\GameOutcome;
use App\Models\Game\Game;

/**
 * None handler - no penalty for timeout.
 *
 * Used for casual games or turn-based games where time limits
 * are suggestions rather than strict requirements.
 */
class NoneHandler implements TimeoutHandlerContract
{
    /**
     * Handle timeout by doing nothing.
     *
     * Game continues normally as if no timeout occurred.
     */
    public function handleTimeout(Game $game, object $gameState, string $timedOutPlayerUlid): GameOutcome
    {
        // No penalty - game continues
        return GameOutcome::inProgress();
    }

    /**
     * Get the handler name.
     */
    public function getName(): string
    {
        return 'none';
    }
}
