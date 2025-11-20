<?php

declare(strict_types=1);

namespace App\GameEngine\Actions;

use App\GameEngine\Interfaces\GameActionContract;

class PlacePiece implements GameActionContract
{
    public function __construct(
        public int $column,
        public ?int $row = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            column: (int) ($data['column'] ?? 0),
            row: isset($data['row']) ? (int) $data['row'] : null
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'column' => $this->column,
            'row' => $this->row,
        ], fn ($value) => $value !== null);
    }

    public function getType(): string
    {
        return 'drop_piece';
    }
}
