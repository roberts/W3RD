<?php

namespace App\GameEngine\Redactors;

use App\GameEngine\Interfaces\GameRedactor;
use App\Models\Auth\User;
use App\Models\Games\Game;

class NullGameRedactor implements GameRedactor
{
    /**
     * @return array<string, mixed>
     */
    public function redact(Game $game, ?User $user): array
    {
        return $game->game_state ?? [];
    }
}
