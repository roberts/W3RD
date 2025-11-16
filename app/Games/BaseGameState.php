<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\GamePhase;
use App\Enums\GameStatus;

/**
 * Abstract base class for all game states.
 *
 * Provides common structure and methods for game state management across
 * all game types. Subclasses add game-specific state (boards, cards, etc).
 *
 * Key features:
 * - Player array supporting variable player counts (2-10+ players)
 * - Phase system for complex turn structures
 * - Immutable with fluent withX() methods
 * - Serialization support for database storage
 *
 * Example subclass:
 * ```php
 * class ValidateFourGameState extends BaseGameState
 * {
 *     public function __construct(
 *         array $players,
 *         ?string $currentPlayerUlid,
 *         ?string $winnerUlid,
 *         GamePhase $phase,
 *         GameStatus $status,
 *         public readonly array $board,
 *         public readonly int $rows,
 *         public readonly int $columns,
 *     ) {
 *         parent::__construct($players, $currentPlayerUlid, $winnerUlid, $phase, $status);
 *     }
 * }
 * ```
 */
abstract class BaseGameState
{
    /**
     * Create a new base game state.
     *
     * @param  array<string, object>  $players  Map of player ULID to player state object
     * @param  string|null  $currentPlayerUlid  ULID of player whose turn it is (null for simultaneous/finished games)
     * @param  string|null  $winnerUlid  ULID of winning player (null if game not won yet)
     * @param  GamePhase  $phase  Current game phase
     * @param  GameStatus  $status  Current game lifecycle status
     */
    public function __construct(
        public readonly array $players,
        public readonly ?string $currentPlayerUlid,
        public readonly ?string $winnerUlid,
        public readonly GamePhase $phase,
        public readonly GameStatus $status,
    ) {}

    /**
     * Get the current player's state.
     *
     * @return object|null The current player's state object, or null if no current player
     */
    public function getCurrentPlayer(): ?object
    {
        if ($this->currentPlayerUlid === null) {
            return null;
        }

        return $this->players[$this->currentPlayerUlid] ?? null;
    }

    /**
     * Get a specific player's state by ULID.
     *
     * @param  string  $playerUlid  Player ULID
     * @return object|null The player's state object, or null if not found
     */
    public function getPlayer(string $playerUlid): ?object
    {
        return $this->players[$playerUlid] ?? null;
    }

    /**
     * Get all player ULIDs.
     *
     * @return array<int, string>
     */
    public function getPlayerUlids(): array
    {
        return array_keys($this->players);
    }

    /**
     * Get the number of players.
     */
    public function getPlayerCount(): int
    {
        return count($this->players);
    }

    /**
     * Check if the game has a winner.
     */
    public function hasWinner(): bool
    {
        return $this->winnerUlid !== null;
    }

    /**
     * Check if the game is finished.
     */
    public function isFinished(): bool
    {
        return $this->phase === GamePhase::COMPLETED || $this->status->isFinished();
    }

    /**
     * Create a new state with updated phase.
     *
     * @param  GamePhase  $phase  New phase
     * @return static New state instance
     */
    abstract public function withPhase(GamePhase $phase): static;

    /**
     * Create a new state with updated status.
     *
     * @param  GameStatus  $status  New status
     * @return static New state instance
     */
    abstract public function withStatus(GameStatus $status): static;

    /**
     * Create a new state with a winner.
     *
     * @param  string  $winnerUlid  Winner's ULID
     * @return static New state instance
     */
    abstract public function withWinner(string $winnerUlid): static;

    /**
     * Create a new state with next player's turn.
     *
     * @return static New state instance
     */
    abstract public function withNextPlayer(): static;

    /**
     * Create state from database array.
     *
     * @param  array<string, mixed>  $data  Serialized state data
     * @return static New state instance
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Convert state to array for database storage.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
