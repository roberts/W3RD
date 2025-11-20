<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\GameEngine\GameOutcome;
use App\GameEngine\ValidationResult;
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
 *     public function validateAction(object $gameState, object $action): ValidationResult
 *     {
 *         assert($gameState instanceof ValidateFourGameState);
 *         assert($action instanceof DropDiscAction || $action instanceof PopOutAction);
 *         // Implementation...
 *     }
 * }
 * ```
 *
 * @see \App\Games\ValidateFour\BaseValidateFourMode For Validate Four implementation
 */
interface GameTitleContract
{
    /**
     * Create initial game state for a new game.
     *
     * This method initializes the game state with the provided players. Different games
     * require different numbers of players (2 for Validate Four, 4 for Hearts, variable for Poker).
     *
     * Example:
     * ```php
     * // Two-player game
     * $state = $mode->createInitialState('player1-ulid', 'player2-ulid');
     *
     * // Four-player game (Hearts)
     * $state = $mode->createInitialState('p1-ulid', 'p2-ulid', 'p3-ulid', 'p4-ulid');
     * ```
     *
     * @param  string  ...$playerUlids  Variable number of player ULIDs
     * @return object The initial game state object
     *
     * @throws \InvalidArgumentException If incorrect number of players provided
     */
    public function createInitialState(string ...$playerUlids): object;

    /**
     * Get the fully qualified class name of the game state class.
     *
     * Returns the FQCN of the state class this mode uses (e.g., ValidateFourGameState::class).
     * Allows the controller to dynamically instantiate the correct state class.
     *
     * Example:
     * ```php
     * $stateClass = $mode->getStateClass();
     * $gameState = $stateClass::fromArray($game->game_state);
     * ```
     *
     * @return string Fully qualified class name
     */
    public function getStateClass(): string;

    /**
     * Get the fully qualified class name of the action mapper.
     *
     * Returns the FQCN of the action mapper class for this game (e.g., ValidateFour\ActionMapper::class).
     * Allows the controller to dynamically create actions for different games.
     *
     * Example:
     * ```php
     * $mapperClass = $mode->getActionMapper();
     * $action = $mapperClass::create('drop_piece', ['column' => 3]);
     * ```
     *
     * @return string Fully qualified class name
     */
    public function getActionMapper(): string;

    /**
     * Get the structured rules for this game title.
     *
     * Returns a structured array containing the title, description, and
     * sections of rules, which can be formatted with Markdown.
     */
    public static function getRules(): array;

    /**
     * Validate a player's action.
     *
     * Checks if the action is legal according to the game rules without modifying state.
     * Returns detailed validation result with error information for the UI.
     *
     * Example:
     * ```php
     * $result = $mode->validateAction($gameState, $action);
     * if (!$result->isValid) {
     *     // Display $result->message to user
     *     // Use $result->errorCode for client-side logic
     * }
     * ```
     *
     * @param  object  $gameState  The current game state object (e.g., ValidateFourGameState)
     * @param  object  $action  The action DTO to validate (e.g., DropDiscAction)
     * @return ValidationResult Detailed validation result with error information
     */
    public function validateAction(object $gameState, object $action): ValidationResult;

    /**
     * Apply a valid action to the game state.
     *
     * Assumes the action has already been validated. Returns a new game state object
     * with the action applied. For immutable state objects, this must return a new
     * instance rather than modifying the existing one.
     *
     * @param  object  $gameState  The current game state object
     * @param  object  $action  The action DTO to apply
     * @return object The updated game state object (new instance if immutable)
     */
    public function applyAction(object $gameState, object $action): object;

    /**
     * Check if the game has been won, drawn, or is still in progress.
     *
     * Returns a rich outcome object supporting wins, draws, rankings, and scores.
     * Used by the controller to determine if the game should end.
     *
     * Example:
     * ```php
     * $outcome = $mode->checkEndCondition($gameState);
     * if ($outcome->isFinished) {
     *     if ($outcome->isDraw) {
     *         // Handle draw
     *     } elseif ($outcome->winnerUlid) {
     *         // Handle win
     *     }
     * }
     * ```
     *
     * @param  object  $gameState  The current game state object
     * @return GameOutcome The game outcome (finished/in-progress, winner, draw, scores, etc.)
     */
    public function checkEndCondition(object $gameState): GameOutcome;

    /**
     * Get available actions for a specific player.
     *
     * Returns array of legal actions the player can take in the current game state.
     * Used for client-side validation and UI updates.
     *
     * Example return format:
     * ```php
     * [
     *     'drop_piece' => ['columns' => [0, 1, 2, 4, 5]],  // Column 3 is full
     *     'pop_out' => ['columns' => [0, 2]],             // Only these have player's discs
     * ]
     * ```
     *
     * @param  object  $gameState  The current game state object
     * @param  string  $playerUlid  The player's ULID
     * @return array<string, mixed> Map of action types to their available parameters
     */
    public function getAvailableActions(object $gameState, string $playerUlid): array;

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
     * @param  object  $gameState  The current game state object
     * @param  Game  $game  The game model instance (to access last action timestamp)
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
