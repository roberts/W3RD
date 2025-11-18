<?php

declare(strict_types=1);

namespace App\Games\Checkers\Actions;

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
     */
    public static function create(string $type, array $data): object
    {
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
            default => throw new \InvalidArgumentException("Unknown action type: {$type}"),
        };
    }
}
