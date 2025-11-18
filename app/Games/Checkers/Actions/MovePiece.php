<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Interfaces\GameActionContract;

/**
 * Action for moving a piece in Checkers.
 */
class MovePiece implements GameActionContract
{
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $toRow,
        public readonly int $toCol,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'move_piece';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'from_row' => $this->fromRow,
            'from_col' => $this->fromCol,
            'to_row' => $this->toRow,
            'to_col' => $this->toCol,
        ];
    }
}
