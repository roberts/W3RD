<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

use App\Games\ValidateFour\Actions\DropDiscAction;
use App\Games\ValidateFour\Actions\PopOutAction;

class ActionFactory
{
    /**
     * Create an action DTO from request data.
     *
     * @param string $actionType The type of action (drop_disc, pop_out, etc.)
     * @param array<string, mixed> $data The action data from the request
     * @return object The action DTO
     * @throws \InvalidArgumentException If action type is unknown
     */
    public static function create(string $actionType, array $data): object
    {
        return match ($actionType) {
            'drop_disc' => new DropDiscAction(
                column: $data['column'] ?? throw new \InvalidArgumentException('Missing column for drop_disc action')
            ),
            'pop_out' => new PopOutAction(
                column: $data['column'] ?? throw new \InvalidArgumentException('Missing column for pop_out action')
            ),
            default => throw new \InvalidArgumentException("Unknown action type: {$actionType}"),
        };
    }

    /**
     * Validate that the action type exists and has required fields.
     *
     * @param string $actionType The type of action to validate
     * @param array<string, mixed> $data The action data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $actionType, array $data): bool
    {
        return match ($actionType) {
            'drop_disc', 'pop_out' => isset($data['column']) && is_int($data['column']),
            default => false,
        };
    }
}
