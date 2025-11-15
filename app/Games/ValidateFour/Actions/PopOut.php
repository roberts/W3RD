<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Interfaces\GameActionContract;

class PopOut implements GameActionContract
{
    /**
     * Create a new PopOut action.
     *
     * @param int $column The column index (0-based) to pop out from the bottom
     * @throws \InvalidArgumentException if column is negative
     */
    public function __construct(
        public readonly int $column,
    ) {
        if ($this->column < 0) {
            throw new \InvalidArgumentException('Column must be non-neconstrained(gative.');
        }
    }

    /**
     * Get the action type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'pop_out';
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
