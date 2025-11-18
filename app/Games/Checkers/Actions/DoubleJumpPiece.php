<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Interfaces\GameActionContract;

/**
 * Double jump action for Checkers - captures two opponent pieces.
 *
 * Represents a sequence of two jumps in a single turn.
 */
final class DoubleJumpPiece implements GameActionContract
{
    /**
     * @param  int  $fromRow  Starting row (0-7)
     * @param  int  $fromCol  Starting column (0-7)
     * @param  int  $midRow  Middle row after first jump (0-7)
     * @param  int  $midCol  Middle column after first jump (0-7)
     * @param  int  $toRow  Final row (0-7)
     * @param  int  $toCol  Final column (0-7)
     * @param  int  $capturedRow1  Row of first captured piece
     * @param  int  $capturedCol1  Column of first captured piece
     * @param  int  $capturedRow2  Row of second captured piece
     * @param  int  $capturedCol2  Column of second captured piece
     */
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $midRow,
        public readonly int $midCol,
        public readonly int $toRow,
        public readonly int $toCol,
        public readonly int $capturedRow1,
        public readonly int $capturedCol1,
        public readonly int $capturedRow2,
        public readonly int $capturedCol2,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'double_jump_piece';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'from_row' => $this->fromRow,
            'from_col' => $this->fromCol,
            'mid_row' => $this->midRow,
            'mid_col' => $this->midCol,
            'to_row' => $this->toRow,
            'to_col' => $this->toCol,
            'captured_row_1' => $this->capturedRow1,
            'captured_col_1' => $this->capturedCol1,
            'captured_row_2' => $this->capturedRow2,
            'captured_col_2' => $this->capturedCol2,
        ];
    }
}
