<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Interfaces\GameActionContract;

/**
 * Triple jump action for Checkers - captures three opponent pieces.
 *
 * Represents a sequence of three jumps in a single turn.
 */
final class TripleJumpPiece implements GameActionContract
{
    /**
     * @param  int  $fromRow  Starting row (0-7)
     * @param  int  $fromCol  Starting column (0-7)
     * @param  int  $mid1Row  First middle row (0-7)
     * @param  int  $mid1Col  First middle column (0-7)
     * @param  int  $mid2Row  Second middle row (0-7)
     * @param  int  $mid2Col  Second middle column (0-7)
     * @param  int  $toRow  Final row (0-7)
     * @param  int  $toCol  Final column (0-7)
     * @param  int  $capturedRow1  Row of first captured piece
     * @param  int  $capturedCol1  Column of first captured piece
     * @param  int  $capturedRow2  Row of second captured piece
     * @param  int  $capturedCol2  Column of second captured piece
     * @param  int  $capturedRow3  Row of third captured piece
     * @param  int  $capturedCol3  Column of third captured piece
     */
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $mid1Row,
        public readonly int $mid1Col,
        public readonly int $mid2Row,
        public readonly int $mid2Col,
        public readonly int $toRow,
        public readonly int $toCol,
        public readonly int $capturedRow1,
        public readonly int $capturedCol1,
        public readonly int $capturedRow2,
        public readonly int $capturedCol2,
        public readonly int $capturedRow3,
        public readonly int $capturedCol3,
    ) {}

    /**
     * Get action type identifier.
     */
    public function getType(): string
    {
        return 'triple_jump_piece';
    }

    /**
     * Convert action to array for storage.
     */
    public function toArray(): array
    {
        return [
            'from_row' => $this->fromRow,
            'from_col' => $this->fromCol,
            'mid1_row' => $this->mid1Row,
            'mid1_col' => $this->mid1Col,
            'mid2_row' => $this->mid2Row,
            'mid2_col' => $this->mid2Col,
            'to_row' => $this->toRow,
            'to_col' => $this->toCol,
            'captured_row_1' => $this->capturedRow1,
            'captured_col_1' => $this->capturedCol1,
            'captured_row_2' => $this->capturedRow2,
            'captured_col_2' => $this->capturedCol2,
            'captured_row_3' => $this->capturedRow3,
            'captured_col_3' => $this->capturedCol3,
        ];
    }
}
