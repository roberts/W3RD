<?php

namespace App\GameTitles\Checkers\Enums;

/**
 * Error codes specific to Checkers game logic.
 *
 * These codes represent rule violations unique to Checkers gameplay.
 * Use GameErrorCode for universal game state errors.
 */
enum CheckersActionError: string
{
    /**
     * Player must make a jump when one is available.
     * Severity: error
     * Rule: Mandatory capture rule in checkers
     */
    case MUST_JUMP = 'must_jump';

    /**
     * Jump move is invalid (blocked, wrong direction, etc.).
     * Severity: error
     */
    case INVALID_JUMP = 'invalid_jump';

    /**
     * Selected piece is blocked and cannot move.
     * Severity: error
     */
    case PIECE_BLOCKED = 'piece_blocked';

    /**
     * Invalid piece selection (empty square, opponent's piece, etc.).
     * Severity: error
     */
    case INVALID_PIECE_SELECTION = 'invalid_piece_selection';

    /**
     * Move direction is invalid for this piece type.
     * Severity: error
     * Note: Regular pieces can only move forward, kings can move any diagonal
     */
    case INVALID_MOVE_DIRECTION = 'invalid_move_direction';

    /**
     * Destination square is occupied.
     * Severity: error
     */
    case DESTINATION_OCCUPIED = 'destination_occupied';

    /**
     * Move is not along a diagonal.
     * Severity: error
     */
    case MUST_MOVE_DIAGONALLY = 'must_move_diagonally';

    /**
     * Move distance is invalid (must be 1 square or jump 2).
     * Severity: error
     */
    case INVALID_MOVE_DISTANCE = 'invalid_move_distance';

    /**
     * The capture move is invalid (e.g. not jumping over a piece).
     * Severity: error
     */
    case INVALID_CAPTURE = 'invalid_capture';

    /**
     * Position coordinates are outside the 8x8 board.
     * Severity: error
     */
    case POSITION_OUT_OF_BOUNDS = 'position_out_of_bounds';

    /**
     * Multi-jump sequence is not completed.
     * Severity: warning
     */
    case MULTI_JUMP_INCOMPLETE = 'multi_jump_incomplete';

    /**
     * Get the severity level for this error code.
     */
    public function severity(): string
    {
        return match ($this) {
            self::MULTI_JUMP_INCOMPLETE => 'warning',
            default => 'error',
        };
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::PIECE_BLOCKED,
            self::DESTINATION_OCCUPIED => true, // Can try different piece/destination
            default => false,
        };
    }
}
