<?php

declare(strict_types=1);

namespace App\Services\Timeouts;

use App\Games\GameOutcome;
use App\Models\Game\Game;

/**
 * Contract for timeout handling implementations.
 *
 * Different games may handle timeouts differently. This interface allows
 * each game mode to define its own timeout behavior.
 */
interface TimeoutHandlerContract
{
    /**
     * Handle a player timeout.
     *
     * @param Game $game The game instance
     * @param object $gameState The current game state
     * @param string $timedOutPlayerUlid The player who timed out
     * @return GameOutcome The outcome after applying timeout penalty
     */
    public function handleTimeout(Game $game, object $gameState, string $timedOutPlayerUlid): GameOutcome;

    /**
     * Get the handler name.
     *
     * @return string Handler name (e.g., 'forfeit', 'pass', 'none')
     */
    public function getName(): string;
}
