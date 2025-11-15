<?php

namespace App\Games\ValidateFour;

class ValidateFourGameState
{
    public array $board;
    public int $board_width;
    public int $board_height;
    public int $connect_length;
    public string $current_player_ulid;
    public array $player_ulids;
    public array $player_map;
    public ?string $winner_ulid;
    public bool $is_draw;

    /**
     * Create a new game state from database data or initialize a new game.
     *
     * @param array $stateData The game state array from the database
     */
    public function __construct(array $stateData = [])
    {
        // Initialize from existing state or create new game
        $this->board = $stateData['board'] ?? [];
        $this->board_width = $stateData['board_width'] ?? 7;
        $this->board_height = $stateData['board_height'] ?? 6;
        $this->connect_length = $stateData['connect_length'] ?? 4;
        $this->current_player_ulid = $stateData['current_player_ulid'] ?? '';
        $this->player_ulids = $stateData['player_ulids'] ?? [];
        $this->player_map = $stateData['player_map'] ?? [];
        $this->winner_ulid = $stateData['winner_ulid'] ?? null;
        $this->is_draw = $stateData['is_draw'] ?? false;

        // If board is empty, initialize it
        if (empty($this->board)) {
            $this->initializeBoard();
        }
    }

    /**
     * Initialize an empty board based on board dimensions.
     */
    private function initializeBoard(): void
    {
        $this->board = [];
        for ($col = 0; $col < $this->board_width; $col++) {
            $this->board[$col] = array_fill(0, $this->board_height, 0);
        }
    }

    /**
     * Convert the game state back to an array for database storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'board' => $this->board,
            'board_width' => $this->board_width,
            'board_height' => $this->board_height,
            'connect_length' => $this->connect_length,
            'current_player_ulid' => $this->current_player_ulid,
            'player_ulids' => $this->player_ulids,
            'player_map' => $this->player_map,
            'winner_ulid' => $this->winner_ulid,
            'is_draw' => $this->is_draw,
        ];
    }

    /**
     * Get the player number (1 or 2) for a given player ULID.
     *
     * @param string $playerUlid
     * @return int|null
     */
    public function getPlayerNumber(string $playerUlid): ?int
    {
        foreach ($this->player_map as $number => $ulid) {
            if ($ulid === $playerUlid) {
                return (int) $number;
            }
        }
        return null;
    }

    /**
     * Get the disc value at a specific position.
     *
     * @param int $column
     * @param int $row
     * @return int 0 for empty, 1 or 2 for player discs
     */
    public function getDiscAt(int $column, int $row): int
    {
        if ($column < 0 || $column >= $this->board_width || $row < 0 || $row >= $this->board_height) {
            return 0;
        }
        return $this->board[$column][$row] ?? 0;
    }

    /**
     * Set the disc value at a specific position.
     *
     * @param int $column
     * @param int $row
     * @param int $value
     */
    public function setDiscAt(int $column, int $row, int $value): void
    {
        if ($column >= 0 && $column < $this->board_width && $row >= 0 && $row < $this->board_height) {
            $this->board[$column][$row] = $value;
        }
    }

    /**
     * Find the lowest empty row in a column.
     *
     * @param int $column
     * @return int|null The row index, or null if column is full
     */
    public function getLowestEmptyRow(int $column): ?int
    {
        if ($column < 0 || $column >= $this->board_width) {
            return null;
        }

        for ($row = 0; $row < $this->board_height; $row++) {
            if ($this->board[$column][$row] === 0) {
                return $row;
            }
        }
        return null; // Column is full
    }

    /**
     * Check if the board is completely full.
     *
     * @return bool
     */
    public function isBoardFull(): bool
    {
        for ($col = 0; $col < $this->board_width; $col++) {
            if ($this->getLowestEmptyRow($col) !== null) {
                return false;
            }
        }
        return true;
    }
}
