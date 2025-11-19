<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Interfaces\GameActionContract;

class DropPiece implements GameActionContract
{
    /**
     * Create a new DropPiece action.
     *
     * @param  int  $column  The column index (0-based) to drop the piece into
     */
    public function __construct(
        public readonly int $column,
    ) {
        // Validation happens in BaseValidateFour::validateDropPiece()
        // This allows for consistent error responses with proper error codes
    }

    /**
     * Get the action type identifier.
     * Returns 'drop_piece' to match the ActionType enum.
     */
    public function getType(): string
    {
        return 'drop_piece';
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
