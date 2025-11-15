<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Game\Game;
use Carbon\Carbon;

/**
 * Universal contract for all game title implementations.
 *
 * This interface defines the core operations that every game mode must implement,
 * regardless of the game title (Validate Four, Chess, etc.).
 *
 * ## Type Safety Design Decision
 *
 * The interface intentionally uses generic `object` types for `$gameState` and `$action`
 * parameters instead of specific types like `ValidateFourGameState`. This design choice
 * supports:
 *
 * 1. **Multi-Game Architecture**: Different games have different state structures and
 *    action types. Using `object` allows implementations for Chess, Poker, etc. without
 *    forcing all games to share a common state structure.
 *
 * 2. **Implementation Flexibility**: Each game mode class can enforce specific types
 *    through PHPDoc and runtime validation while the interface remains generic.
 *
 * 3. **Future Extensibility**: New games can be added without modifying the interface
 *    or breaking existing implementations.
 *
 * Example implementation:
 * ```php
 * class StandardMode implements GameTitleContract
 * {
 *     // PHPDoc provides type hints for specific game
 *     public function validateAction(object $gameState, object $action): bool
 *     {
 *         assert($gameState instanceof ValidateFourGameState);
 *         assert($action instanceof DropDiscAction || $action instanceof PopOutAction);
 *         // Implementation...
 *     }
 * }
 * ```
 *
 * @see \App\Games\ValidateFour\AbstractValidateFourMode For Validate Four implementation
 */
interface GameTitleContract
{
    /**
     * Validate a player's action.
     *
     * Checks if the action is legal according to the game rules without modifying state.
     * Must return bool - never throw exceptions during validation.
     *
     * @param object $gameState The current game state object (e.g., ValidateFourGameState)
     * @param object $action The action DTO to validate (e.g., DropDiscAction)
     * @return bool True if the action is valid, false otherwise
     */
    public function validateAction(object $gameState, object $action): bool;

    /**
     * Apply a valid action to the game state.
     *
     * Assumes the action has already been validated. Returns a new game state object
     * with the action applied. For immutable state objects, this must return a new
     * instance rather than modifying the existing one.
     *
     * @param object $gameState The current game state object
     * @param object $action The action DTO to apply
     * @return object The updated game state object (new instance if immutable)
     */
    public function applyAction(object $gameState, object $action): object;

    /**
     * Check if the game has been won or drawn.
     *
     * Returns the ULID of the winning player, or null if the game continues.
     * Does not check for draws - the controller handles that separately via
     * isBoardFull() or similar game-specific logic.
     *
     * @param object $gameState The current game state object
     * @return string|null The winning player's ULID, or null if game continues
     */
    public function checkEndCondition(object $gameState): ?string;

    /**
     * Get the timelimit in seconds for each action.
     *
     * The base time allowed for a player to make their move. The actual deadline
     * includes a grace period (see getActionDeadline).
     *
     * @return int Timelimit in seconds (e.g., 30 for Validate Four)
     */
    public function getTimelimit(): int;

    /**
     * Get the deadline timestamp for the current action.
     *
     * Calculates when the current player's turn expires, including any grace periods.
     * Used by the controller to check for timeouts.
     *
     * @param object $gameState The current game state object
     * @param Game $game The game model instance (to access last action timestamp)
     * @return Carbon The deadline timestamp
     */
    public function getActionDeadline(object $gameState, Game $game): Carbon;

    /**
     * Get the penalty applied when an action times out.
     *
     * Determines what happens when a player fails to make their move within the
     * time limit. Implementations must return one of:
     * - 'none': No penalty, game continues
     * - 'pass': Turn is skipped to the other player
     * - 'forfeit': Player loses the game immediately
     *
     * @return string Penalty type: 'none', 'pass', or 'forfeit'
     */
    public function getTimeoutPenalty(): string;
}
