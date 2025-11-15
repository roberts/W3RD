<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

/**
 * Immutable game state for Validate Four (Connect Four variant).
 *
 * This class uses an immutable architecture where state cannot be modified after creation.
 * All state changes return a new instance with the updated values.
 *
 * ## Board Structure
 * The board is a 2D array: `board[row][column]` where:
 * - `null` = empty space
 * - `string` = player ULID who owns the disc
 * - Row 0 is the top, row (rows-1) is the bottom where discs land
 *
 * ## Factory Methods
 * Use these static methods to create instances:
 * - `createNew($playerOneUlid, $playerTwoUlid, $columns, $rows, $connectCount)` - New game
 * - `fromArray($data)` - Restore from database JSON
 *
 * ## Fluent State Changes
 * Use `withX()` methods to create new instances with changes:
 * ```php
 * // Drop a disc and advance to next player
 * $newState = $gameState
 *     ->withDiscAt($row, $column, $playerUlid)
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
 * $disc = $gameState->getDiscAt($row, $column);  // Returns player ULID or null
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
class ValidateFourGameState
{
    /**
     * Board structure: board[row][column] where:
     * - null = empty space
     * - string = player ULID who owns the disc
     * 
     * Row 0 is the top, row (rows-1) is the bottom where discs land.
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

    /** @var string ULID of the player whose turn it is */
    public readonly string $currentPlayerUlid;

    /** @var string ULID of player one */
    public readonly string $playerOneUlid;

    /** @var string ULID of player two */
    public readonly string $playerTwoUlid;

    /** @var string|null ULID of the winning player, null if no winner yet */
    public readonly ?string $winnerUlid;

    /** @var bool True if game ended in a draw */
    public readonly bool $isDraw;

    /**
     * Private constructor - use static factory methods to create instances.
     *
     * @param array<int, array<int, string|null>> $board The game board
     * @param string $playerOneUlid ULID of player one
     * @param string $playerTwoUlid ULID of player two
     * @param string $currentPlayerUlid ULID of current player
     * @param int $columns Number of columns
     * @param int $rows Number of rows
     * @param int $connectCount Number to connect to win
     * @param string|null $winnerUlid ULID of winner
     * @param bool $isDraw Whether game is a draw
     */
    private function __construct(
        array $board,
        string $playerOneUlid,
        string $playerTwoUlid,
        string $currentPlayerUlid,
        int $columns,
        int $rows,
        int $connectCount,
        ?string $winnerUlid = null,
        bool $isDraw = false,
    ) {
        $this->board = $board;
        $this->playerOneUlid = $playerOneUlid;
        $this->playerTwoUlid = $playerTwoUlid;
        $this->currentPlayerUlid = $currentPlayerUlid;
        $this->columns = $columns;
        $this->rows = $rows;
        $this->connectCount = $connectCount;
        $this->winnerUlid = $winnerUlid;
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
     * @param string $playerOneUlid ULID of player one (goes first)
     * @param string $playerTwoUlid ULID of player two
     * @param int $columns Number of columns (default: 7)
     * @param int $rows Number of rows (default: 6)
     * @param int $connectCount Number to connect to win (default: 4)
     * @return self New immutable game state instance
     */
    public static function createNew(
        string $playerOneUlid,
        string $playerTwoUlid,
        int $columns = 7,
        int $rows = 6,
        int $connectCount = 4
    ): self {
        // Initialize empty board: rows x columns, all null
        $board = array_fill(0, $rows, array_fill(0, $columns, null));

        return new self(
            board: $board,
            playerOneUlid: $playerOneUlid,
            playerTwoUlid: $playerTwoUlid,
            currentPlayerUlid: $playerOneUlid,
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
     * @param array<string, mixed> $stateData Associative array from database JSON
     * @return self Restored immutable game state instance
     */
    public static function fromArray(array $stateData): self
    {
        return new self(
            board: $stateData['board'] ?? [],
            playerOneUlid: $stateData['player_one_ulid'] ?? '',
            playerTwoUlid: $stateData['player_two_ulid'] ?? '',
            currentPlayerUlid: $stateData['current_player_ulid'] ?? '',
            columns: $stateData['columns'] ?? 7,
            rows: $stateData['rows'] ?? 6,
            connectCount: $stateData['connect_count'] ?? 4,
            winnerUlid: $stateData['winner_ulid'] ?? null,
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
        return [
            'board' => $this->board,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'connect_count' => $this->connectCount,
            'current_player_ulid' => $this->currentPlayerUlid,
            'player_one_ulid' => $this->playerOneUlid,
            'player_two_ulid' => $this->playerTwoUlid,
            'winner_ulid' => $this->winnerUlid,
            'is_draw' => $this->isDraw,
        ];
    }

    /**
     * Get the disc owner at a specific position (row, column).
     *
     * @param int $row
     * @param int $column
     * @return string|null Player ULID or null if empty
     */
    public function getDiscAt(int $row, int $column): ?string
    {
        if ($row < 0 || $row >= $this->rows || $column < 0 || $column >= $this->columns) {
            return null;
        }
        return $this->board[$row][$column];
    }

    /**
     * Find the lowest empty row in a column (where disc would land).
     * Returns the row index from bottom to top.
     *
     * @param int $column
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
     *
     * @return bool
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
     * Create a new state with a disc placed at the specified position.
     * Returns a new immutable instance.
     *
     * @param int $row
     * @param int $column
     * @param string $playerUlid
     * @return self
     */
    public function withDiscAt(int $row, int $column, string $playerUlid): self
    {
        $newBoard = $this->board;
        $newBoard[$row][$column] = $playerUlid;

        return new self(
            board: $newBoard,
            playerOneUlid: $this->playerOneUlid,
            playerTwoUlid: $this->playerTwoUlid,
            currentPlayerUlid: $this->currentPlayerUlid,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            winnerUlid: $this->winnerUlid,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with the turn switched to the other player.
     *
     * @return self
     */
    public function withNextPlayer(): self
    {
        $nextPlayer = $this->currentPlayerUlid === $this->playerOneUlid
            ? $this->playerTwoUlid
            : $this->playerOneUlid;

        return new self(
            board: $this->board,
            playerOneUlid: $this->playerOneUlid,
            playerTwoUlid: $this->playerTwoUlid,
            currentPlayerUlid: $nextPlayer,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            winnerUlid: $this->winnerUlid,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create a new state with a winner set.
     *
     * @param string $winnerUlid
     * @return self
     */
    public function withWinner(string $winnerUlid): self
    {
        return new self(
            board: $this->board,
            playerOneUlid: $this->playerOneUlid,
            playerTwoUlid: $this->playerTwoUlid,
            currentPlayerUlid: $this->currentPlayerUlid,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            winnerUlid: $winnerUlid,
            isDraw: false,
        );
    }

    /**
     * Create a new state marked as a draw.
     *
     * @return self
     */
    public function withDraw(): self
    {
        return new self(
            board: $this->board,
            playerOneUlid: $this->playerOneUlid,
            playerTwoUlid: $this->playerTwoUlid,
            currentPlayerUlid: $this->currentPlayerUlid,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            winnerUlid: null,
            isDraw: true,
        );
    }

    /**
     * Create a new state with an updated board (for complex operations like pop out).
     *
     * @param array $newBoard
     * @return self
     */
    public function withBoard(array $newBoard): self
    {
        return new self(
            board: $newBoard,
            playerOneUlid: $this->playerOneUlid,
            playerTwoUlid: $this->playerTwoUlid,
            currentPlayerUlid: $this->currentPlayerUlid,
            columns: $this->columns,
            rows: $this->rows,
            connectCount: $this->connectCount,
            winnerUlid: $this->winnerUlid,
            isDraw: $this->isDraw,
        );
    }
}
