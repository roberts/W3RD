<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Progression;

use App\Enums\GameAttributes\GameSequence;
use App\Models\Games\Game;

/**
 * Handles turn advancement logic for sequential and phase-based games.
 */
class TurnAdvancer
{
    /**
     * Advance the game to the next turn or phase.
     *
     * @param  Game  $game  The game instance
     * @param  GameSequence  $sequence  The game's sequence attribute
     * @return Game The updated game instance
     */
    public function advance(Game $game, GameSequence $sequence): Game
    {
        return match ($sequence) {
            GameSequence::SEQUENTIAL => $this->advanceSequential($game),
            GameSequence::PHASE_BASED => $this->advancePhaseBased($game),
            GameSequence::SIMULTANEOUS => $this->advanceSimultaneous($game),
            GameSequence::INTERLEAVED => $this->advanceInterleaved($game),
        };
    }

    /**
     * Advance sequential turn-based games.
     */
    protected function advanceSequential(Game $game): Game
    {
        $game->increment('turn_number');

        return $game;
    }

    /**
     * Advance phase-based games (e.g., Hearts rounds, Poker betting rounds).
     */
    protected function advancePhaseBased(Game $game): Game
    {
        // Phase-based games typically manage their own phase transitions
        // in the game state, but we still track the global turn number
        $game->increment('turn_number');

        return $game;
    }

    /**
     * Advance simultaneous games (all players act at once).
     */
    protected function advanceSimultaneous(Game $game): Game
    {
        // In simultaneous games, we advance once all players have submitted
        $game->increment('turn_number');

        return $game;
    }

    /**
     * Advance interleaved games (players can react out of turn).
     */
    protected function advanceInterleaved(Game $game): Game
    {
        // Interleaved games may not have strict turn boundaries
        // Turn number remains unchanged for interleaved sequences
        return $game;
    }
}
