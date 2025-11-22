<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Sequence;

use App\GameEngine\Interfaces\SequenceDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class SimultaneousDriver implements SequenceDriver
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
