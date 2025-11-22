<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\EntryPolicy;

use App\Enums\GameStatus;
use App\GameEngine\Interfaces\EntryPolicyDriver;
use App\Models\Auth\User;
use App\Models\Game\Game;

class LockedOnStartDriver implements EntryPolicyDriver
{
    public function canJoin(Game $game, User $player): bool
    {
        // Cannot join if the game is active or finished.
        return ! in_array($game->status, [GameStatus::ACTIVE, GameStatus::COMPLETED]);
    }
}
