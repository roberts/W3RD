<?php

declare(strict_types=1);

namespace App\GameEngine\TimerExpired;

use App\GameEngine\GameOutcome;
use App\Models\Game\Game;

/**
 * Contract for timer expiration handling implementations.
 *
 * Different games may handle timer expirations differently. This interface allows
 * each game mode to define its own timer expiration behavior.
 */
interface HandlerContract
{
    /**
     * Handle a player's timer expiring.
     *
     * @param  Game  $game  The game instance
     * @param  object  $gameState  The current game state
     * @param  string  $playerUlid  The player whose timer expired
     * @return GameOutcome The outcome after applying the penalty
     */
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome;

    /**
     * Get the handler name.
     *
     * @return string Handler name (e.g., 'forfeit', 'pass', 'none')
     */
    public function getName(): string;
}
