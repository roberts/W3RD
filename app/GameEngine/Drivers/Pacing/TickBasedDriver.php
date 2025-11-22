<?php

declare(strict_types=1);

namespace App\GameEngine\Drivers\Pacing;

use App\GameEngine\Interfaces\PacingDriver;
use App\Models\Game\Game;

class TickBasedDriver implements PacingDriver
{
    public function startTurnTimer(Game $game): void
    {
        // Timers are irrelevant; the server tick drives the game.
    }

    public function validateActionTime(Game $game): void
    {
        // Actions are likely queued and processed on the next tick,
        // so time validation is not on the action itself.
    }
}
