<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\Games\BaseGameState;

/**
 * Immutable game state for Validate Four (Connect Four variant).
 *
 * This class extends BaseGameState and adds Validate Four-specific state
 * (board, dimensions, connect count).
 *
 * ## Board Structure
 * The board is a 2D array: `board[row][column]` where:
 * - `null` = empty space
 * - `string` = player ULID who owns the piece
 * - Row 0 is the top, row (rows-1) is the bottom where pieces land
 *
 * ## Factory Methods
 * Use these static methods to create instances:
 * - `createNew($playerOneUlid, $playerTwoUlid, $columns, $rows, $connectCount)` - New game
 * - `fromArray($data)` - Restore from database JSON
 *
 * ## Fluent State Changes
 * Use `withX()` methods to create new instances with changes:
 * ```php
 * // Drop a piece and advance to next player
 * $newState = $gameState
 *     ->withPieceAt($row, $column, $playerUlid)
 *     ->withNextPlayer();
 *
 * // Mark winner
 * $newState = $gameState->withWinner($winnerUlid);
 *
 * // Mark draw
 * $newState = $gameState->withDraw();
 *
 * // Replace entire board (for pop-out mode)
 * $newState = $gameState->withBoard($newBoardArray)->withNextPlayer();
 * ```
 *
 * ## Querying State
 * ```php
 * $piece = $gameState->getPieceAt($row, $column);  // Returns player ULID or null
 * $emptyRow = $gameState->getLowestEmptyRow($column);  // Returns row index or null if column full
 * $isFull = $gameState->isBoardFull();  // Returns bool
 * ```
 *
 * ## Persistence
 * ```php
 * // Save to database
 * $game->game_state = $gameState->toArray();
 *
 * // Restore from database
 * $gameState = ValidateFourGameState::fromArray($game->game_state);
 * ```
 *
 * @see \App\Interfaces\GameTitleContract For the interface all game modes must implement
 */
final class GameState extends BaseGameState
{
    /**
     * Board structure: board[row][column] where:
     * - null = empty space
     * - string = player ULID who owns the piece
     *
     * Row 0 is the top, row (rows-1) is the bottom where pieces land.
     *
     * @var array<int, array<int, string|null>>
     */
    public readonly array $board;

    /** @var int Number of columns on the board */
    public readonly int $columns;

    /** @var int Number of rows on the board */
    public readonly int $rows;

    /** @var int Number of discs to connect in a row to win */
    public readonly int $connectCount;

    /** @var bool True if game ended in a draw */
    public readonly bool $isDraw;

    /**
     * Create a new Validate Four game state.
     *
     * @param  array<string, PlayerState>  $players  Map of player ULID to PlayerState
     * @param  string|null  $currentPlayerUlid  ULID of current player
     * @param  string|null  $winnerUlid  ULID of winner
     * @param  GamePhase  $phase  Current game phase
     * @param  GameStatus  $status  Current game status
     * @param  array<int, array<int, string|null>>  $board  The game board
     * @param  int  $columns  Number of columns
     * @param  int  $rows  Number of rows
     * @param  int  $connectCount  Number to connect to win
     * @param  bool  $isDraw  Whether game is a draw
     */
    public function __construct(
        array $players,
        ?string $currentPlayerUlid,
        ?string $winnerUlid,
        GamePhase $phase,
        GameStatus $status,
        array $board,
        int $columns,
        int $rows,
        int $connectCount,
        bool $isDraw = false,
    ) {
        parent::__construct($players, $currentPlayerUlid, $winnerUlid, $phase, $status);

        $this->board = $board;
        $this->columns = $columns;
        $this->rows = $rows;
        $this->connectCount = $connectCount;
        $this->isDraw = $isDraw;
    }

    /**
     * Create a new game state for a fresh game.
     *
     * Initializes an empty board with all positions set to null.
     * Player one always goes first.
     *
     * Example:
     * ```php
     * $gameState = ValidateFourGameState::createNew(
     *     playerOneUlid: '01HXYZ...',
     *     playerTwoUlid: '01HXAB...',
     *     columns: 7,
     *     rows: 6,
     *     connectCount: 4
     * );
     * ```
     *
     * @param  string  $playerOneUlid  ULID of player one (goes first)
     * @param  string  $playerTwoUlid  ULID of player two
     * @param  int  $columns  Number of columns (default: 7)
     * @param  int  $rows  Number of rows (default: 6)
     * @param  int  $connectCount  Number to connect to win (default: 4)
     * @return static New immutable game state instance
     */
    public static function createNew(
        string $playerOneUlid,
        string $playerTwoUlid,
        int $columns = 7,
        int $rows = 6,
        int $connectCount = 4
    ): static {
        // Initialize empty board: rows x columns, all null
        $board = array_fill(0, $rows, array_fill(0, $columns, null));

        // Create player states
        $players = [
            $playerOneUlid => new PlayerState(
                ulid: $playerOneUlid,
                position: 1,
                color: 'red'
            ),
            $playerTwoUlid => new PlayerState(
                ulid: $playerTwoUlid,
                position: 2,
                color: 'yellow'
            ),
        ];

        return new self(
            players: $players,
            currentPlayerUlid: $playerOneUlid,
            winnerUlid: null,
            phase: GamePhase::ACTIVE,
            status: GameStatus::ACTIVE,
            board: $board,
            columns: $columns,
            rows: $rows,
            connectCount: $connectCount,
        );
    }

    /**
     * Restore game state from database array.
     *
     * Use this to deserialize game state from the JSON stored in the database.
     * Handles missing keys with sensible defaults.
     *
     * Example:
     * ```php
     * $game = Game::find($id);
     * $gameState = ValidateFourGameState::fromArray($game->game_state);
     * ```
     *
     * @param  array<string, mixed>  $stateData  Associative array from database JSON
     * @return static Restored immutable game state instance
     */
    public static function fromArray(array $stateData): static
    {
        // Restore players from array
        $players = [];
        foreach ($stateData['players'] ?? [] as $ulid => $playerData) {
            $players[$ulid] = PlayerState::fromArray($playerData);
        }

        // Parse phase and status enums
        $phase = isset($stateData['phase'])
            ? GamePhase::from($stateData['phase'])
            : GamePhase::ACTIVE;

        $status = isset($stateData['status'])
            ? GameStatus::from($stateData['status'])
            : GameStatus::ACTIVE;

        return new static(
            players: $players,
            currentPlayerUlid: $stateData['current_player_ulid'] ?? null,
            winnerUlid: $stateData['winner_ulid'] ?? null,
            phase: $phase,
            status: $status,
            board: $stateData['board'] ?? [],
            columns: $stateData['columns'] ?? 7,
            rows: $stateData['rows'] ?? 6,
            connectCount: $stateData['connect_count'] ?? 4,
            isDraw: $stateData['is_draw'] ?? false,
        );
    }

    /**
     * Convert the game state back to an array for database storage.
     *
     * Serializes the immutable state to an associative array that can be
     * stored as JSON in the database.
     *
     * Example:
     * ```php
     * $game->game_state = $gameState->toArray();
     * $game->save();
     * ```
     *
     * @return array<string, mixed> Associative array ready for JSON encoding
     */
    public function toArray(): array
    {
        // Serialize players to array
        $playersArray = [];
        foreach ($this->players as $ulid => $playerState) {
            $playersArray[$ulid] = $playerState->toArray();
        }

        return [
            'players' => $playersArray,
            'current_player_ulid' => $this->currentPlayerUlid,
            'winner_ulid' => $this->winnerUlid,
            'phase' => $this->phase->value,
            'status' => $this->status->value,
            'board' => $this->board,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'connect_count' => $this->connectCount,
            'is_draw' => $this->isDraw,
        ];
    }

    /**
     * Get the piece owner at a specific position (row, column).
     *
     * @return string|null Player ULID or null if empty
     */
    public function getPieceAt(int $row, int $column): ?string
    {
        if ($row < 0 || $row >= $this->rows || $column < 0 || $column >= $this->columns) {
            return null;
        }

        return $this->board[$row][$column];
    }

    /**
     * Find the lowest empty row in a column (where piece would land).
     * Returns the row index from bottom to top.
     *
     * @return int|null The row index, or null if column is full
     */
    public function getLowestEmptyRow(int $column): ?int
    {
        if ($column < 0 || $column >= $this->columns) {
            return null;
        }

        // Start from bottom row (rows - 1) and work up
        for ($row = $this->rows - 1; $row >= 0; $row--) {
            if ($this->board[$row][$column] === null) {
                return $row;
            }
        }

        return null; // Column is full
    }

    /**
     * Check if the board is completely full (draw condition).
     */
    public function isBoardFull(): bool
    {
        // Check top row - if all filled, board is full
        for ($col = 0; $col < $this->columns; $col++) {
            if ($this->board[0][$col] === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new state with a piece placed at the specified position.
     * Returns a new immutable instance.
     */
    public function withPieceAt(int $row, int $column, string $playerUlid): self
    {
        $newBoard = $this->board;
        $newBoard[$row][$column] = $playerUlid;

        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $newBoard,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with the turn switched to the other player.
     */
    public function withNextPlayer(): static
    {
        // Get player ULIDs in position order
        $playersByPosition = $this->players;
        uasort($playersByPosition, fn ($a, $b) => $a->position <=> $b->position);
        $playerUlids = array_keys($playersByPosition);

        // Find current player index and advance to next
        $currentIndex = array_search($this->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % count($playerUlids);
        $nextPlayerUlid = $playerUlids[$nextIndex];

        return new static(
            players: $this->players,
            currentPlayerUlid: $nextPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $this->board,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with updated phase.
     *
     * @param  GamePhase  $phase  New phase
     */
    public function withPhase(GamePhase $phase): static
    {
        return new static(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $phase,
            status: $this->status,
            board: $this->board,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with updated status.
     *
     * @param  GameStatus  $status  New status
     */
    public function withStatus(GameStatus $status): static
    {
        return new static(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $status,
            board: $this->board,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with a winner set.
     */
    public function withWinner(string $winnerUlid): static
    {
        return new static(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $winnerUlid,
            phase: GamePhase::COMPLETED,
            status: GameStatus::COMPLETED,
            board: $this->board,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: false,
        );
    }

    /**
     * Create a new state marked as a draw.
     */
    public function withDraw(): self
    {
        return new static(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: null,
            phase: GamePhase::COMPLETED,
            status: GameStatus::COMPLETED,
            board: $this->board,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: true,
        );
    }

    /**
     * Create a new state with a completely new board.
     */
    public function withBoard(array $newBoard): self
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $newBoard,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            isDraw: $this->isDraw,
        );
    }
}
