<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Interfaces\GameActionContract;

class PopOut implements GameActionContract
{
    /**
     * Create a new PopOut action.
     *
     * @param  int  $column  The column index (0-based) to pop out from the bottom
     */
    public function __construct(
        public readonly int $column,
    ) {
        // Validation happens in PopOutMode::validatePopOutAction()
        // This allows for consistent error responses with proper error codes
    }

    /**
     * Get the action type identifier.
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
