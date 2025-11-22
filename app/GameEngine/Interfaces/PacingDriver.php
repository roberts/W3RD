<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Models\Game\Game;

interface PacingDriver
{
    public function startTurnTimer(Game $game): void;

    public function validateActionTime(Game $game): void;
}
