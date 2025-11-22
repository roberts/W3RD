<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Models\Game\Game;
use App\Models\Auth\User;

interface EntryPolicyDriver
{
    public function canJoin(Game $game, User $player): bool;
}
