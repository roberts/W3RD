<?php

namespace App\GameTitles\Contracts;

interface GameModeContract
{
    /**
     * Get the unique string identifier for this game mode (e.g., "standard").
     */
    public static function getIdentifier(): string;

    /**
     * Process a player action and return the new game state.
     *
     * @param  object  $gameState  The current GameState object.
     * @param  ActionContract  $action  The action to process.
     * @return object The new GameState object after the action is applied.
     */
    public function processAction(object $gameState, ActionContract $action): object;

    /**
     * Check if an action is valid against the current game state.
     *
     * @param  object  $gameState  The current GameState object.
     * @param  ActionContract  $action  The action to validate.
     * @return bool True if the action is valid, false otherwise.
     */
    public function isActionValid(object $gameState, ActionContract $action): bool;

    /**
     * Check the game state for a winner.
     *
     * @param  object  $gameState  The current GameState object.
     * @return string|null The ULID of the winning player, or null if there is no winner yet.
     */
    public function checkForWinner(object $gameState): ?string;
}
