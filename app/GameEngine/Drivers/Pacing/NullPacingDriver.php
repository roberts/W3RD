<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Pacing;

use App\GameEngine\Interfaces\PacingDriver;
use App\Models\Game\Game;

class NullPacingDriver implements PacingDriver
{
    public function startTurnTimer(Game $game): void
    {
        // Do nothing
    }

    public function validateActionTime(Game $game): void
    {
        // Do nothing
    }
}
