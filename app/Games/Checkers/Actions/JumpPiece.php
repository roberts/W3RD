<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Interfaces\GameActionContract;

/**
 * Jump action for Checkers - captures one opponent piece.
 *
 * Represents a single jump over an opponent's piece.
 */
final class JumpPiece implements GameActionContract
{
    /**
     * @param  int  $fromRow  Starting row (0-7)
     * @param  int  $fromCol  Starting column (0-7)
     * @param  int  $toRow  Ending row (0-7)
     * @param  int  $toCol  Ending column (0-7)
     * @param  int  $capturedRow  Row of captured piece
     * @param  int  $capturedCol  Column of captured piece
     */
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $toRow,
        public readonly int $toCol,
        public readonly int $capturedRow,
        public readonly int $capturedCol,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'jump_piece';
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
            'captured_row' => $this->capturedRow,
            'captured_col' => $this->capturedCol,
        ];
    }
}
