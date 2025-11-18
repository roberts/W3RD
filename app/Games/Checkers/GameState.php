<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\Games\BaseGameState;

/**
 * Immutable game state for Checkers.
 *
 * This class extends BaseGameState and adds Checkers-specific state
 * (board, piece positions, and game rules).
 *
 * ## Board Structure
 * The board is a 2D array: `board[row][column]` where:
 * - `null` = empty space
 * - `array` = piece data: ['player' => 'ulid', 'king' => bool]
 * - Row 0 is the top (black's starting side)
 * - Row 7 is the bottom (red's starting side)
 *
 * ## Factory Methods
 * Use these static methods to create instances:
 * - `createNew($playerOneUlid, $playerTwoUlid)` - New game
 * - `fromArray($data)` - Restore from database JSON
 *
 * ## Fluent State Changes
 * Use `withX()` methods to create new instances with changes:
 * ```php
 * $newState = $gameState
 *     ->withMovedPiece($fromRow, $fromCol, $toRow, $toCol)
 *     ->withNextPlayer();
 * ```
 */
final class GameState extends BaseGameState
{
    /**
     * Board structure: board[row][column] where:
     * - null = empty space
     * - array = ['player' => 'ulid', 'king' => bool]
     *
     * @var array<int, array<int, array{player: string, king: bool}|null>>
     */
    public readonly array $board;

    /** @var bool True if game ended in a draw */
    public readonly bool $isDraw;

    /**
     * Create a new Checkers game state.
     *
     * @param  array<string, PlayerState>  $players  Map of player ULID to PlayerState
     * @param  string|null  $currentPlayerUlid  ULID of current player
     * @param  string|null  $winnerUlid  ULID of winner
     * @param  GamePhase  $phase  Current game phase
     * @param  GameStatus  $status  Current game status
     * @param  array<int, array<int, array{player: string, king: bool}|null>>  $board  The game board
     * @param  bool  $isDraw  Whether the game is a draw
     */
    public function __construct(
        array $players,
        ?string $currentPlayerUlid,
        ?string $winnerUlid,
        GamePhase $phase,
        GameStatus $status,
        array $board,
        bool $isDraw = false,
    ) {
        parent::__construct($players, $currentPlayerUlid, $winnerUlid, $phase, $status);
        $this->board = $board;
        $this->isDraw = $isDraw;
    }

    /**
     * Create initial game state for a new Checkers game.
     *
     * @param  string  $playerOneUlid  First player (red pieces, bottom)
     * @param  string  $playerTwoUlid  Second player (black pieces, top)
     * @return self New game state
     */
    public static function createNew(string $playerOneUlid, string $playerTwoUlid): self
    {
        // Initialize empty 8x8 board
        $board = array_fill(0, 8, array_fill(0, 8, null));

        // Place red pieces (rows 5-7) for player one
        for ($row = 5; $row <= 7; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($row + $col) % 2 === 1) { // Only on dark squares
                    $board[$row][$col] = ['player' => $playerOneUlid, 'king' => false];
                }
            }
        }

        // Place black pieces (rows 0-2) for player two
        for ($row = 0; $row <= 2; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($row + $col) % 2 === 1) { // Only on dark squares
                    $board[$row][$col] = ['player' => $playerTwoUlid, 'king' => false];
                }
            }
        }

        $players = [
            $playerOneUlid => new PlayerState($playerOneUlid, 'red', 12),
            $playerTwoUlid => new PlayerState($playerTwoUlid, 'black', 12),
        ];

        return new self(
            players: $players,
            currentPlayerUlid: $playerOneUlid, // Red starts
            winnerUlid: null,
            phase: GamePhase::ACTIVE,
            status: GameStatus::ACTIVE,
            board: $board,
            isDraw: false,
        );
    }

    /**
     * Create game state from array.
     *
     * @param  array<string, mixed>  $data  Serialized game state
     */
    public static function fromArray(array $data): static
    {
        $players = [];
        foreach ($data['players'] ?? [] as $ulid => $playerData) {
            $players[$ulid] = PlayerState::fromArray($playerData);
        }

        return new self(
            players: $players,
            currentPlayerUlid: $data['currentPlayerUlid'] ?? null,
            winnerUlid: $data['winnerUlid'] ?? null,
            phase: isset($data['phase']) ? GamePhase::from($data['phase']) : GamePhase::ACTIVE,
            status: isset($data['status']) ? GameStatus::from($data['status']) : GameStatus::ACTIVE,
            board: $data['board'] ?? [],
            isDraw: $data['isDraw'] ?? false,
        );
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'players' => array_map(fn ($p) => $p->toArray(), $this->players),
            'currentPlayerUlid' => $this->currentPlayerUlid,
            'winnerUlid' => $this->winnerUlid,
            'phase' => $this->phase->value,
            'status' => $this->status->value,
            'board' => $this->board,
            'isDraw' => $this->isDraw,
        ];
    }

    /**
     * Get piece at a specific position.
     *
     * @param  int  $row  Row index (0-7)
     * @param  int  $col  Column index (0-7)
     * @return array{player: string, king: bool}|null Piece data or null if empty
     */
    public function getPieceAt(int $row, int $col): ?array
    {
        return $this->board[$row][$col] ?? null;
    }

    /**
     * Create new state with moved piece.
     *
     * @param  int  $fromRow  Source row
     * @param  int  $fromCol  Source column
     * @param  int  $toRow  Destination row
     * @param  int  $toCol  Destination column
     * @return self New state with piece moved
     */
    public function withMovedPiece(int $fromRow, int $fromCol, int $toRow, int $toCol): self
    {
        $newBoard = $this->board;
        $piece = $newBoard[$fromRow][$fromCol];

        // Check for king promotion
        if ($piece !== null) {
            // Red pieces king at row 0, black pieces king at row 7
            /** @var PlayerState $playerState */
            $playerState = $this->players[$piece['player']];
            if (($playerState->color === 'red' && $toRow === 0) ||
                ($playerState->color === 'black' && $toRow === 7)) {
                $piece['king'] = true;
            }
        }

        $newBoard[$toRow][$toCol] = $piece;
        $newBoard[$fromRow][$fromCol] = null;

        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $newBoard,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with removed piece (for captures).
     *
     * @param  int  $row  Row of piece to remove
     * @param  int  $col  Column of piece to remove
     * @return self New state with piece removed
     */
    public function withRemovedPiece(int $row, int $col): self
    {
        $newBoard = $this->board;
        $removedPiece = $newBoard[$row][$col];
        $newBoard[$row][$col] = null;

        // Update player pieces count
        $newPlayers = $this->players;
        if ($removedPiece !== null) {
            $playerUlid = $removedPiece['player'];
            /** @var PlayerState $playerState */
            $playerState = $newPlayers[$playerUlid];
            $newPlayers[$playerUlid] = $playerState->withPiecesRemaining($playerState->piecesRemaining - 1);
        }

        return new self(
            players: $newPlayers,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $newBoard,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with updated phase.
     *
     * @param  GamePhase  $phase  New game phase
     */
    public function withPhase(GamePhase $phase): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $phase,
            status: $this->status,
            board: $this->board,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with updated status.
     *
     * @param  GameStatus  $status  New game status
     */
    public function withStatus(GameStatus $status): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: $this->currentPlayerUlid,
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $status,
            board: $this->board,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with next player.
     *
     * @return static New state with turn advanced
     */
    public function withNextPlayer(): static
    {
        $playerUlids = $this->getPlayerUlids();
        $currentIndex = array_search($this->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % count($playerUlids);

        return new self(
            players: $this->players,
            currentPlayerUlid: $playerUlids[$nextIndex],
            winnerUlid: $this->winnerUlid,
            phase: $this->phase,
            status: $this->status,
            board: $this->board,
            isDraw: $this->isDraw,
        );
    }

    /**
     * Create new state with winner.
     *
     * @param  string  $winnerUlid  Winner's ULID
     * @return static New state with game ended
     */
    public function withWinner(string $winnerUlid): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: null,
            winnerUlid: $winnerUlid,
            phase: GamePhase::COMPLETED,
            status: GameStatus::COMPLETED,
            board: $this->board,
            isDraw: false,
        );
    }

    /**
     * Create new state with draw.
     *
     * @return static New state with game ended in draw
     */
    public function withDraw(): static
    {
        return new self(
            players: $this->players,
            currentPlayerUlid: null,
            winnerUlid: null,
            phase: GamePhase::COMPLETED,
            status: GameStatus::COMPLETED,
            board: $this->board,
            isDraw: true,
        );
    }
}
