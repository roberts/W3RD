<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Models\Auth\User;
use App\Models\Games\Game;

interface EntryPolicyDriver
{
    public function canJoin(Game $game, User $player): bool;
}
