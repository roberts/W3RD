<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Progression;

use App\Enums\GameAttributes\GameSequence;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Models\Games\Game;

/**
 * Manages turn progression based on game sequence type.
 */
class TurnManager
{
    /**
     * Advance the turn counter based on the game's sequence attribute.
     */
    public function advanceTurn(Game $game, GameTitleContract $mode): void
    {
        switch ($mode->getSequence()) {
            case GameSequence::SEQUENTIAL:
                $game->increment('turn_number');
                break;
            case GameSequence::SIMULTANEOUS:
            case GameSequence::INTERLEAVED:
                // In real-time/simultaneous games, turns might not auto-increment in the same way.
                break;
        }
    }
}
