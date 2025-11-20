<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

use App\Games\BasePlayerState;

/**
 * Player state for Validate Four games.
 *
 * Contains player-specific information for a Validate Four game instance.
 * Immutable - use withX() methods to create modified copies.
 */
class ValidateFourPlayer extends BasePlayerState
{
    /**
     * Create a new player state.
     *
     * @param  string  $ulid  Player's ULID
     * @param  int  $position  Player position (1 or 2)
     * @param  string  $color  Player's piece color (e.g., 'red', 'yellow')
     */
    public function __construct(
        string $ulid,
        public readonly int $position,
        public readonly string $color,
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
            position: $data['position'],
            color: $data['color'],
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
            'position' => $this->position,
            'color' => $this->color,
        ];
    }
}
