<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Sequence;

use App\Models\Auth\User;
use App\Models\Game\Game;

/**
 * Interleaved turn gameplay.
 * Players can act out of order based on game state or initiative.
 * 
 * Examples: Magic: The Gathering (stack/priority), Real-time strategy (unit initiative)
 */
trait InterleavedTurns
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        // Interleaved games may allow multiple players to act based on priority or game state.
        // Override this method to implement game-specific priority logic.
        return true; // Placeholder - override in game implementation
    }

    public function advanceTurn(Game $game): Game
    {
        // Logic to determine next player based on priority/initiative system.
        // Override this method to implement game-specific turn order logic.
        return $game;
    }
}
