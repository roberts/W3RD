<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\EntryPolicy;

use App\GameEngine\Interfaces\EntryPolicyDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class ReplacementOnlyDriver implements EntryPolicyDriver
{
    public function canJoin(Game $game, User $player): bool
    {
        // Can join if there is an open slot from a disconnected player.
        $hasOpenSlot = $game->players()->where('status', 'disconnected')->exists();

        return $hasOpenSlot;
    }
}
