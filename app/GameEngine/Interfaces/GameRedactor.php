<?php

namespace App\GameEngine\Interfaces;

use App\Models\Auth\User;
use App\Models\Games\Game;

interface GameRedactor
{
    public function redact(Game $game, ?User $user): array;
}
