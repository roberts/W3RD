<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\GameEngine\GameOutcome;

interface GameArbiterContract
{
    /**
     * Check if the game has reached a win or draw condition.
     *
     * @param  object  $state  The current game state
     * @return GameOutcome|null The outcome if the game ended, or null if it continues
     */
    public function checkWinCondition(object $state): ?GameOutcome;
}
