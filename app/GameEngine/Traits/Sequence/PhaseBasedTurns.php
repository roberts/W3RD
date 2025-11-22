<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Sequence;

use App\Models\Auth\User;
use App\Models\Game\Game;

/**
 * Phase-based gameplay with different rules per phase.
 * Game progresses through distinct phases, each with its own turn logic.
 *
 * Examples: Hearts (passing phase → trick-taking), Poker (betting rounds)
 */
trait PhaseBasedTurns
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        $phase = $game->game_state['current_phase'] ?? null;

        // Delegate to a phase-specific handler to determine who can act.
        // Games using this trait should override this method with phase-specific logic.
        return true; // Placeholder - override in game implementation
    }

    public function advanceTurn(Game $game): Game
    {
        // Logic to advance the phase or the player within the phase.
        // Games using this trait should override this method with phase-specific logic.
        return $game;
    }
}
