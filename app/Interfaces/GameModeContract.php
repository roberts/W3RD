<?php

namespace App\Interfaces;

use App\Models\Game\Player;

interface GameModeContract
{
    /**
     * Validate a player's action.
     *
     * @param object $gameState The current game state object
     * @param object $action The action DTO to validate
     * @return bool True if the action is valid, false otherwise
     */
    public function validateAction(object $gameState, object $action): bool;

    /**
     * Apply a valid action to the game state.
     *
     * @param object $gameState The current game state object
     * @param object $action The action DTO to apply
     * @return object The updated game state object
     */
    public function applyAction(object $gameState, object $action): object;

    /**
     * Check if the game has been won, lost, or drawn.
     *
     * @param object $gameState The current game state object
     * @return Player|null The winning player, or null if game continues
     */
    public function checkEndCondition(object $gameState): ?Player;
}
