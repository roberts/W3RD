<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\GameEngine\Interfaces\GameActionContract;

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
     * Create a new PopOut action from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            column: (int) ($data['column'] ?? 0),
        );
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
