<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\Actions;

use App\Exceptions\InvalidActionDataException;
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
     * @throws InvalidActionDataException If action type is unknown or data is invalid
     */
    public static function create(string $actionType, array $data): GameActionContract
    {
        // Validate action type
        if (! in_array($actionType, self::getSupportedActionTypes(), true)) {
            throw new InvalidActionDataException(
                sprintf('Unknown action type: %s', $actionType),
                'unknown_action_type',
                'validate-four',
                [
                    'action_type' => $actionType,
                    'supported_types' => self::getSupportedActionTypes(),
                ]
            );
        }

        // Validate required fields
        if (! isset($data['column'])) {
            throw new InvalidActionDataException(
                sprintf('Missing required field: column for %s action', $actionType),
                'missing_required_field',
                'validate-four',
                [
                    'action_type' => $actionType,
                    'missing_field' => 'column',
                    'required_fields' => ['column'],
                ]
            );
        }

        // Validate field types
        if (! is_int($data['column'])) {
            throw new InvalidActionDataException(
                sprintf('Field "column" must be an integer, %s provided', gettype($data['column'])),
                'invalid_field_type',
                'validate-four',
                [
                    'field' => 'column',
                    'expected_type' => 'integer',
                    'actual_type' => gettype($data['column']),
                    'actual_value' => $data['column'],
                ]
            );
        }

        return match ($actionType) {
            'drop_piece' => new DropPiece(column: $data['column']),
            'pop_out' => new PopOut(column: $data['column']),
            default => throw new \LogicException('Action type validation failed but match statement reached'),
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
