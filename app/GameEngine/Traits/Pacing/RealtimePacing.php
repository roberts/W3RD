<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Pacing;

use App\Models\Game\Game;

/**
 * Real-time pacing with sub-second input required.
 * Game state updates continuously (60fps).
 *
 * Examples: Counter-Strike, Rocket League, Street Fighter
 */
trait RealtimePacing
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
