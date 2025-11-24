<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\Enums\GameAttributes\GameContinuity;
use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameAttributes\GameEntryPolicy;
use App\Enums\GameAttributes\GameLifecycle;
use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameTimer;
use App\Enums\GameAttributes\GameVisibility;
use App\Exceptions\Game\TurnTimerExpiredException;
use App\GameEngine\GameOutcome;
use App\GameEngine\ValidationResult;
use App\Models\Auth\User;
use App\Models\Games\Game;
use Carbon\Carbon;

/**
 * Universal contract for all game title implementations.
 *
 * This interface defines the core operations that every game mode must implement,
 * regardless of the game title (Connect Four, Chess, etc.).
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
 * @see \App\Games\ValidateFour\BaseValidateFourMode For Connect Four implementation
 */
interface GameTitleContract
{
    // Engine-Critical Attributes
    public static function getPacing(): GamePacing;

    public static function getTimer(): GameTimer;

    public static function getSequence(): GameSequence;

    public static function getVisibility(): GameVisibility;

    public static function getDynamic(): GameDynamic;

    public static function getContinuity(): GameContinuity;

    public static function getEntryPolicy(): GameEntryPolicy;

    public static function getLifecycle(): GameLifecycle;

    /**
     * @return array<class-string<\BackedEnum>, \BackedEnum>
     */
    public static function getAdditionalAttributes(): array;

    /**
     * Create initial game state for a new game.
     *
     * This method initializes the game state with the provided players. Different games
     * require different numbers of players (2 for Connect Four, 4 for Hearts, variable for Poker).
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
     * Get the arbiter for checking game win conditions.
     *
     * Returns the arbiter responsible for determining if the game has ended
     * and what the outcome is (win, loss, draw, etc.).
     *
     * @return GameArbiterContract The arbiter for this game mode
     */
    public function getArbiter(): GameArbiterContract;

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
     *
     * @return array<string, mixed>
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
     * @return int Timelimit in seconds (e.g., 30 for Connect Four)
     */
    public function getTimelimit(): int;

    /**
     * Get the redactor for this game title.
     *
     * Returns the appropriate redactor based on the game's visibility attribute.
     * Full information games return NullGameRedactor, while hidden information games
     * return game-specific redactors that hide private information from opponents.
     *
     * @return GameRedactor The redactor instance for this game
     */
    public function getRedactor(): GameRedactor;

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

    /**
     * Check if it's the specified player's turn to act.
     *
     * Determines whether the given player is allowed to take an action in the
     * current game state. Used by GameKernel to validate player actions.
     *
     * Implementations are typically provided by sequence traits:
     * - SequentialTurns: Checks current_player_id
     * - SimultaneousTurns: Always returns true
     * - PhaseBasedTurns: Checks phase-specific logic
     * - InterleavedTurns: Checks team-based turns
     *
     * @param  Game  $game  The game instance
     * @param  User  $player  The player attempting to act
     * @return bool True if it's the player's turn
     */
    public function isPlayerTurn(Game $game, User $player): bool;

    /**
     * Advance the game to the next turn or phase.
     *
     * Updates the game state to reflect the next player's turn or advance
     * to the next phase. Used by GameKernel after actions are processed.
     *
     * Implementations are typically provided by sequence traits:
     * - SequentialTurns: Rotates to next player
     * - SimultaneousTurns: No-op or phase transition
     * - PhaseBasedTurns: Advances phase or player within phase
     * - InterleavedTurns: Alternates between teams
     *
     * @param  Game  $game  The game instance
     * @return Game The updated game instance
     */
    public function advanceTurn(Game $game): Game;

    /**
     * Start the timer for the current turn.
     *
     * Initializes timing for the current player's turn, typically by setting
     * turn_ends_at and dispatching timer expiration jobs. Used by GameKernel
     * after turn advancement.
     *
     * Implementations are typically provided by pacing traits:
     * - SynchronousPacing: Sets short timer (seconds to minutes)
     * - AsynchronousPacing: Sets long timer (hours to days)
     * - RealtimePacing: Continuous timer
     * - TickBasedPacing: Discrete time units
     *
     * @param  Game  $game  The game instance
     */
    public function startTurnTimer(Game $game): void;

    /**
     * Validate that the action is being taken within the allowed time.
     *
     * Checks if the current action is being made before the turn timer expires.
     * Throws exception if the timer has expired. Used by GameKernel before
     * validating player actions.
     *
     * Implementations are typically provided by pacing traits:
     * - SynchronousPacing: Validates against turn_ends_at
     * - AsynchronousPacing: Validates with long timeout
     * - RealtimePacing: Validates continuous time
     * - TickBasedPacing: Validates tick-based time
     *
     * @param  Game  $game  The game instance
     *
     * @throws TurnTimerExpiredException If time has expired
     */
    public function validateActionTime(Game $game): void;

    /**
     * Redact sensitive information from game state for a specific player.
     *
     * Returns a version of the game state with hidden information removed
     * or obscured based on what the player should be able to see. Used by
     * GameKernel when returning state to clients.
     *
     * Implementations are typically provided by visibility traits:
     * - FullInformation: Returns state unchanged
     * - HiddenInformation: Redacts opponent's private data
     * - FogOfWar: Redacts based on visibility range
     * - AsymmetricInformation: Role-based redaction
     *
     * @param  object  $gameState  The complete game state
     * @param  User  $player  The player viewing the state
     * @return object The redacted game state for this player
     */
    public function redact(object $gameState, User $player): object;

    /**
     * Handle timer expiration for a player.
     *
     * Determines the outcome when a player's turn timer expires. The action taken
     * depends on the game's timer penalty configuration. Used by TimerExpiredHandler
     * when processing expired turn timers.
     *
     * Implementations are typically provided by timer expired traits:
     * - ForfeitOnTimerExpired: Player loses immediately
     * - PassOnTimerExpired: Turn is skipped, game continues
     * - NoTimerExpiredPenalty: No action taken
     *
     * @param  Game  $game  The game instance
     * @param  object  $gameState  The current game state
     * @param  string  $playerUlid  The ULID of the player whose timer expired
     * @return GameOutcome The outcome of the timer expiration
     */
    public function handleTimerExpired(Game $game, object $gameState, string $playerUlid): GameOutcome;
}
