<?php

declare(strict_types=1);

namespace App\Games\ValidateFour\ErrorCodes;

/**
 * Error codes specific to Pop Out mode in ValidateFour.
 *
 * These errors only apply when playing the Pop Out variant.
 * Use ValidateFourActionError for standard drop_piece errors.
 * Use BaseGameActionErrorCode for universal game state errors.
 */
enum PopOutModeError: string
{
    /**
     * Attempted to pop out a piece when there is no piece at the bottom of the column.
     *
     * Severity: error
     * Retryable: true (player can try a different column)
     * Context: column number
     */
    case NO_PIECE_AT_BOTTOM = 'no_piece_at_bottom';

    /**
     * Attempted to pop out an opponent's piece from the bottom row.
     * Players can only pop out their own pieces.
     *
     * Severity: error
     * Retryable: true (player can try a different column)
     * Context: column number, piece_owner
     */
    case NOT_YOUR_PIECE = 'not_your_piece';

    /**
     * Attempted to pop out when the column would become unstable.
     * Some variants may restrict popping if it would create disconnected pieces.
     *
     * Severity: error
     * Retryable: true (player can try a different column or action)
     * Context: column number
     */
    case INVALID_POP_STATE = 'invalid_pop_state';

    /**
     * Get the severity level for this error code.
     *
     * @return 'error'|'warning'
     */
    public function severity(): string
    {
        return 'error';
    }

    /**
     * Check if this error is retryable.
     *
     * @return bool True if the player can retry with different parameters
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::NO_PIECE_AT_BOTTOM,
            self::NOT_YOUR_PIECE,
            self::INVALID_POP_STATE => true,
        };
    }
}
