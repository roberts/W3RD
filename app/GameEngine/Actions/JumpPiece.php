<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class JumpPiece implements GameActionContract
{
    public function __construct(
        public readonly int $fromRow,
        public readonly int $fromCol,
        public readonly int $toRow,
        public readonly int $toCol,
        public readonly int $capturedRow,
        public readonly int $capturedCol,
    ) {}

    public function getType(): string
    {
        return 'jump_piece';
    }

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
