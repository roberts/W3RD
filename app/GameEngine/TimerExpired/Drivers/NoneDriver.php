<?php

declare(strict_types=1);

namespace App\GameEngine\TimerExpired\Drivers;

use App\GameEngine\GameOutcome;
use App\GameEngine\TimerExpired\HandlerContract;
use App\Models\Game\Game;

/**
 * None driver - no penalty for timer expiration.
 *
 * Used for casual games or turn-based games where time limits
 * are suggestions rather than strict requirements.
 */
class NoneDriver implements HandlerContract
{
    /**
     * Handle timer expiration by doing nothing.
     *
     * Game continues normally as if no timer expired.
     */
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome
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
