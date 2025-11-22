<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Sequence;

use App\GameEngine\Interfaces\SequenceDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class PhaseBasedDriver implements SequenceDriver
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        $phase = $game->game_state['current_phase'] ?? null;

        // Delegate to a phase-specific handler to determine who can act.
        // e.g., in Poker, only certain players can act in the 'betting' phase.
        return true; // Placeholder
    }

    public function advanceTurn(Game $game): Game
    {
        // Logic to advance the phase or the player within the phase.
        return $game;
    }
}
