<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Sequence;

use App\Models\Auth\User;
use App\Models\Game\Game;

/**
 * Simultaneous action gameplay.
 * All players act at the same time, then actions resolve together.
 *
 * Examples: Rock Paper Scissors, Diplomacy movement phase
 */
trait SimultaneousTurns
{
    public function isPlayerTurn(Game $game, User $player): bool
    {
        // In simultaneous games, it's always "everyone's turn" during the action phase.
        // Logic to check if player has already submitted their action for the turn would go here.
        return true;
    }

    public function advanceTurn(Game $game): Game
    {
        // Turn advances after all players have submitted their actions.
        // This would be triggered by a different mechanism.
        return $game;
    }
}
