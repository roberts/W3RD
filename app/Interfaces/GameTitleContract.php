<?php

namespace App\Interfaces;

use App\Models\Game\Game;
use App\Models\Game\Player;
use Carbon\Carbon;

interface GameTitleContract
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

    /**
     * Get the timelimit in seconds for each action.
     *
     * @return int Timelimit in seconds
     */
    public function getTimelimit(): int;

    /**
     * Get the deadline timestamp for the current action.
     *
     * @param object $gameState The current game state object
     * @param Game $game The game model instance
     * @return Carbon The deadline timestamp
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon;

    /**
     * Get the penalty applied when an action times out.
     *
     * @return string Penalty type: 'none', 'pass', or 'forfeit'
     */
    public function getTimeoutPenalty(): string;
}
