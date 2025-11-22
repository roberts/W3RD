<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Pacing;

use App\GameEngine\Interfaces\PacingDriver;
use App\Models\Game\Game;

class RealtimeDriver implements PacingDriver
{
    public function startTurnTimer(Game $game): void
    {
        // Real-time games might not have explicit turn timers,
        // but could have action cooldowns managed elsewhere.
    }

    public function validateActionTime(Game $game): void
    {
        // Validation might be based on server tick rate or action cooldowns
    }
}
