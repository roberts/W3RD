<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\EntryPolicy;

use App\GameEngine\Interfaces\EntryPolicyDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class DropInDropOutDriver implements EntryPolicyDriver
{
    public function canJoin(Game $game, User $player): bool
    {
        // Can join as long as the game is not full.
        return $game->players->count() < $game->max_players;
    }
}
