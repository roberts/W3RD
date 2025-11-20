<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Exceptions\InvalidActionDataException;
use App\GameEngine\Actions\MovePiece;
use App\GameEngine\Actions\JumpPiece;
use App\GameEngine\Actions\DoubleJumpPiece;
use App\GameEngine\Actions\TripleJumpPiece;
use App\GameEngine\Interfaces\ActionMapperContract;
use App\GameEngine\Interfaces\GameActionContract;

/**
 * Action factory for Checkers game actions.
 *
 * Creates action objects from raw input data.
 */
class CheckersActionMapper implements ActionMapperContract
{
    /**
     * Create an action DTO from request data.
     *
     * @param  string  $actionType  The type of action (e.g., 'move_piece', 'jump_piece')
     * @param  array<string, mixed>  $data  The action data from the request
     * @return GameActionContract The action DTO
     *
     * @throws InvalidActionDataException If action type is unknown or data is invalid
     */
    public static function create(string $actionType, array $data): GameActionContract
    {
        // Validate action type
        $supportedTypes = self::getSupportedActionTypes();
        if (! in_array($actionType, $supportedTypes, true)) {
            throw new InvalidActionDataException(
                sprintf('Unknown action type: %s', $actionType),
                'unknown_action_type',
                'checkers',
                [
                    'action_type' => $actionType,
                    'supported_types' => $supportedTypes,
                ]
            );
        }

        // Get required fields for this action type
        $requiredFields = match ($actionType) {
            'move_piece' => ['from_row', 'from_col', 'to_row', 'to_col'],
            'jump_piece' => ['from_row', 'from_col', 'to_row', 'to_col', 'captured_row', 'captured_col'],
            'double_jump_piece' => ['from_row', 'from_col', 'mid_row', 'mid_col', 'to_row', 'to_col', 'captured_row_1', 'captured_col_1', 'captured_row_2', 'captured_col_2'],
            'triple_jump_piece' => ['from_row', 'from_col', 'mid1_row', 'mid1_col', 'mid2_row', 'mid2_col', 'to_row', 'to_col', 'captured_row_1', 'captured_col_1', 'captured_row_2', 'captured_col_2', 'captured_row_3', 'captured_col_3'],
            default => [],
        };

        // Validate all required fields are present
        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                throw new InvalidActionDataException(
                    sprintf('Missing required field: %s for %s action', $field, $actionType),
                    'missing_required_field',
                    'checkers',
                    [
                        'action_type' => $actionType,
                        'missing_field' => $field,
                        'required_fields' => $requiredFields,
                    ]
                );
            }

            // Validate field type (all checkers fields must be integers)
            if (! is_int($data[$field])) {
                throw new InvalidActionDataException(
                    sprintf('Field "%s" must be an integer, %s provided', $field, gettype($data[$field])),
                    'invalid_field_type',
                    'checkers',
                    [
                        'field' => $field,
                        'expected_type' => 'integer',
                        'actual_type' => gettype($data[$field]),
                        'actual_value' => $data[$field],
                    ]
                );
            }
        }

        return match ($actionType) {
            'move_piece' => new MovePiece(
                fromRow: $data['from_row'],
                fromCol: $data['from_col'],
                toRow: $data['to_row'],
                toCol: $data['to_col'],
            ),
            'jump_piece' => new JumpPiece(
                fromRow: $data['from_row'],
                fromCol: $data['from_col'],
                toRow: $data['to_row'],
                toCol: $data['to_col'],
                capturedRow: $data['captured_row'],
                capturedCol: $data['captured_col'],
            ),
            'double_jump_piece' => new DoubleJumpPiece(
                fromRow: $data['from_row'],
                fromCol: $data['from_col'],
                midRow: $data['mid_row'],
                midCol: $data['mid_col'],
                toRow: $data['to_row'],
                toCol: $data['to_col'],
                capturedRow1: $data['captured_row_1'],
                capturedCol1: $data['captured_col_1'],
                capturedRow2: $data['captured_row_2'],
                capturedCol2: $data['captured_col_2'],
            ),
            'triple_jump_piece' => new TripleJumpPiece(
                fromRow: $data['from_row'],
                fromCol: $data['from_col'],
                mid1Row: $data['mid1_row'],
                mid1Col: $data['mid1_col'],
                mid2Row: $data['mid2_row'],
                mid2Col: $data['mid2_col'],
                toRow: $data['to_row'],
                toCol: $data['to_col'],
                capturedRow1: $data['captured_row_1'],
                capturedCol1: $data['captured_col_1'],
                capturedRow2: $data['captured_row_2'],
                capturedCol2: $data['captured_col_2'],
                capturedRow3: $data['captured_row_3'],
                capturedCol3: $data['captured_col_3'],
            ),
            default => throw new InvalidActionDataException(
                sprintf('Unexpected action type: %s', $actionType),
                'unexpected_action_type',
                'checkers'
            ),
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
        if (! in_array($actionType, self::getSupportedActionTypes(), true)) {
            return false;
        }

        // Basic validation logic - could be more robust
        return true;
    }

    /**
     * Get all supported action types for this game.
     *
     * @return array<string> Array of action type identifiers
     */
    public static function getSupportedActionTypes(): array
    {
        return ['move_piece', 'jump_piece', 'double_jump_piece', 'triple_jump_piece'];
    }
}
