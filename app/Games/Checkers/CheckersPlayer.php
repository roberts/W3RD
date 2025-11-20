<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\Games\BasePlayerState;

/**
 * Player state for Checkers games.
 *
 * Contains player-specific information for a Checkers game instance.
 * Immutable - use withX() methods to create modified copies.
 */
class CheckersPlayer extends BasePlayerState
{
    /**
     * Create a new player state.
     *
     * @param  string  $ulid  Player's ULID
     * @param  string  $color  Player's piece color ('red' or 'black')
     * @param  int  $piecesRemaining  Count of pieces still on the board
     */
    public function __construct(
        string $ulid,
        public readonly string $color,
        public readonly int $piecesRemaining,
    ) {
        parent::__construct($ulid);
    }

    /**
     * Create player state from array.
     *
     * @param  array<string, mixed>  $data  Serialized player data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ulid: $data['ulid'],
            color: $data['color'],
            piecesRemaining: $data['piecesRemaining'],
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
            'ulid' => $this->ulid,
            'color' => $this->color,
            'piecesRemaining' => $this->piecesRemaining,
        ];
    }

    /**
     * Create a copy with updated pieces remaining count.
     *
     * @param  int  $piecesRemaining  New pieces count
     * @return self New instance with updated count
     */
    public function withPiecesRemaining(int $piecesRemaining): self
    {
        return new self(
            ulid: $this->ulid,
            color: $this->color,
            piecesRemaining: $piecesRemaining,
        );
    }
}
