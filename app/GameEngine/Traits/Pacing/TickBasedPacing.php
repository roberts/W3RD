<?php

declare(strict_types=1);

namespace App\GameEngine\Traits\Pacing;

use App\Models\Game\Game;

/**
 * Tick-based pacing.
 * Server advances state at fixed intervals regardless of input (1s - 1h ticks).
 * 
 * Examples: Clash of Clans, Travian, Cookie Clicker
 */
trait TickBasedPacing
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
