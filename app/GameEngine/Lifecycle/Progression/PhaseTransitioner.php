<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Progression;

use App\Enums\GamePhase;
use App\Models\Game\Game;

/**
 * Manages phase transitions for phase-based games (e.g., Hearts, Poker).
 */
class PhaseTransitioner
{
    /**
     * Transition to the next phase in the game.
     *
     * @param  Game  $game  The game instance
     * @param  object  $gameState  The current game state
     * @return object The updated game state with new phase
     */
    public function transition(Game $game, object $gameState): object
    {
        // Delegate to game-specific phase logic if available
        if (method_exists($gameState, 'advancePhase')) {
            return $gameState->advancePhase();
        }

        return $gameState;
    }

    /**
     * Check if a phase transition is needed.
     *
     * @param  object  $gameState  The current game state
     * @return bool True if phase should transition
     */
    public function shouldTransition(object $gameState): bool
    {
        if (method_exists($gameState, 'isPhaseComplete')) {
            return $gameState->isPhaseComplete();
        }

        return false;
    }

    /**
     * Get the current phase from the game state.
     *
     * @param  object  $gameState  The game state
     * @return GamePhase|null The current phase if available
     */
    public function getCurrentPhase(object $gameState): ?GamePhase
    {
        if (isset($gameState->phase) && $gameState->phase instanceof GamePhase) {
            return $gameState->phase;
        }

        return null;
    }
}
