<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class DoubleJumpPiece implements GameActionContract
{
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

    public function getType(): string
    {
        return 'double_jump_piece';
    }

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
