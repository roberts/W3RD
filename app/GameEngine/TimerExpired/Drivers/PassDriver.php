<?php

declare(strict_types=1);

namespace App\GameEngine\TimerExpired\Drivers;

use App\GameEngine\GameOutcome;
use App\GameEngine\TimerExpired\HandlerContract;
use App\Models\Game\Game;

/**
 * Pass driver - turn is skipped on timer expiration.
 *
 * Used for games where missing a turn is a sufficient penalty.
 * Game continues with the next player.
 */
class PassDriver implements HandlerContract
{
    /**
     * Handle timer expiration by passing the turn.
     *
     * The game state is advanced to the next player without any action.
     */
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome
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
