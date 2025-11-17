<?php

declare(strict_types=1);

namespace App\Games\ValidateFour;

use App\Games\ValidateFour\Actions\DropPiece;
use App\Games\ValidateFour\Actions\PopOut;
use App\Interfaces\ActionFactoryContract;
use App\Interfaces\GameActionContract;

class ActionFactory implements ActionFactoryContract
{
    /**
     * Create an action DTO from request data.
     *
     * @param  string  $actionType  The type of action (drop_piece, pop_out, etc.)
     * @param  array<string, mixed>  $data  The action data from the request
     * @return GameActionContract The action DTO
     *
     * @throws \InvalidArgumentException If action type is unknown or data is invalid
     */
    public static function create(string $actionType, array $data): GameActionContract
    {
        return match ($actionType) {
            'drop_piece' => new DropPiece(
                column: $data['column'] ?? throw new \InvalidArgumentException('Missing column for drop_piece action')
            ),
            'pop_out' => new PopOut(
                column: $data['column'] ?? throw new \InvalidArgumentException('Missing column for pop_out action')
            ),
            default => throw new \InvalidArgumentException("Unknown action type: {$actionType}"),
        };
    }

    /**
     * Validate that the action type exists and has required fields.
     *
     * @param  string  $actionType  The type of action to validate
     * @param  array<string, mixed>  $data  The action data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $actionType, array $data): bool
    {
        return match ($actionType) {
            'drop_piece', 'pop_out' => isset($data['column']) && is_int($data['column']),
            default => false,
        };
    }

    /**
     * Get all supported action types for Validate Four.
     *
     * @return array<string>
     */
    public static function getSupportedActionTypes(): array
    {
        return ['drop_piece', 'pop_out'];
    }
}
