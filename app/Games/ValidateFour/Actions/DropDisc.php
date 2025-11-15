<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Interfaces\GameActionContract;

class DropDisc implements GameActionContract
{
    /**
     * Create a new DropDisc action.
     *
     * @param int $column The column index (0-based) to drop the disc into
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
     *
     * @return string
     */
    public function getType(): string
    {
        return 'drop_disc';
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
