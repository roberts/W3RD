<?php

namespace App\GameEngine\Interfaces;

use App\Models\Game\Game;
use App\Models\Auth\User;

interface GameRedactor
{
    public function redact(Game $game, ?User $user): array;
}
