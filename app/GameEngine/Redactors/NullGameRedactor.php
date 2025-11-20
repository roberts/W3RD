<?php

namespace App\GameEngine\Redactors;

use App\GameEngine\Interfaces\GameRedactor;
use App\Models\Game\Game;
use App\Models\Auth\User;

class NullGameRedactor implements GameRedactor
{
    public function redact(Game $game, ?User $user): array
    {
        return $game->game_state ?? [];
    }
}
