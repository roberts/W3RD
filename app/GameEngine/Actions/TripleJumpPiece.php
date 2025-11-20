<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class TripleJumpPiece implements GameActionContract
{
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

    public function getType(): string
    {
        return 'triple_jump_piece';
    }

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
