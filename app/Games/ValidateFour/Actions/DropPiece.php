<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Interfaces\GameActionContract;

class DropPiece implements GameActionContract
{
    /**
     * Create a new DropPiece action.
     *
     * @param  int  $column  The column index (0-based) to drop the piece into
     *
     * @throws \InvalidArgumentException if column is negative
     */
    public function __construct(
        public readonly int $column,
    ) {
        if ($this->column < 0) {
            throw new \InvalidArgumentException('Column must be non-negative.');
        }
    }

    /**
     * Get the action type identifier.
     * Returns 'drop_piece' to match the ActionType enum.
     */
    public function getType(): string
    {
        return 'drop_piece';
    }

    /**
     * Convert the action to an array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
        ];
    }
}
