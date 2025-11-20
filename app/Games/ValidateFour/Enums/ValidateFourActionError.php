<?php

namespace App\Games\ValidateFour\Enums;

/**
 * Error codes specific to ValidateFour (Connect Four) game logic.
 *
 * These codes represent rule violations unique to ValidateFour gameplay.
 * Use BaseGameActionErrorCode for universal game state errors.
 */
enum ValidateFourActionError: string
{
    /**
     * The selected column is full and cannot accept more pieces.
     * Severity: error
     */
    case COLUMN_FULL = 'column_full';

    /**
     * Column number is outside the valid range (0-6).
     * Severity: error
     */
    case INVALID_COLUMN = 'invalid_column';

    /**
     * Column index is out of bounds for the board.
     * Severity: error
     */
    case COLUMN_OUT_OF_BOUNDS = 'column_out_of_bounds';

    /**
     * Required action detail 'column' is missing.
     * Severity: error
     */
    case MISSING_COLUMN = 'missing_column';

    /**
     * Board state is corrupted or invalid.
     * Severity: error
     */
    case INVALID_BOARD_STATE = 'invalid_board_state';

    /**
     * Get the severity level for this error code.
     */
    public function severity(): string
    {
        return 'error';
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::COLUMN_FULL => true, // Player can try a different column
            default => false,
        };
    }
}
