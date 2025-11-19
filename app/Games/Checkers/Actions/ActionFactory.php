<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

use App\Exceptions\InvalidActionDataException;

/**
 * Action factory for Checkers game actions.
 *
 * Creates action objects from raw input data.
 */
class ActionFactory
{
    /**
     * Create an action from type and data.
     *
     * @param  string  $type  Action type (e.g., 'move_piece', 'jump_piece')
     * @param  array<string, mixed>  $data  Action data
     * @return object Action object
     *
     * @throws InvalidActionDataException If action type is unknown or data is invalid
     */
    public static function create(string $type, array $data): object
    {
        // Validate action type
        $supportedTypes = ['move_piece', 'jump_piece', 'double_jump_piece', 'triple_jump_piece'];
        if (! in_array($type, $supportedTypes, true)) {
            throw new InvalidActionDataException(
                sprintf('Unknown action type: %s', $type),
                'unknown_action_type',
                'checkers',
                [
                    'action_type' => $type,
                    'supported_types' => $supportedTypes,
                ]
            );
        }

        // Get required fields for this action type
        $requiredFields = match ($type) {
            'move_piece' => ['from_row', 'from_col', 'to_row', 'to_col'],
            'jump_piece' => ['from_row', 'from_col', 'to_row', 'to_col', 'captured_row', 'captured_col'],
            'double_jump_piece' => ['from_row', 'from_col', 'mid_row', 'mid_col', 'to_row', 'to_col', 'captured_row_1', 'captured_col_1', 'captured_row_2', 'captured_col_2'],
            'triple_jump_piece' => ['from_row', 'from_col', 'mid1_row', 'mid1_col', 'mid2_row', 'mid2_col', 'to_row', 'to_col', 'captured_row_1', 'captured_col_1', 'captured_row_2', 'captured_col_2', 'captured_row_3', 'captured_col_3'],
        };

        // Validate all required fields are present
        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                throw new InvalidActionDataException(
                    sprintf('Missing required field: %s for %s action', $field, $type),
                    'missing_required_field',
                    'checkers',
                    [
                        'action_type' => $type,
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

        return match ($type) {
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
        };
    }
}
