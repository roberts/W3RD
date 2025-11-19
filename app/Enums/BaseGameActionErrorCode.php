<?php

namespace App\Enums;

/**
 * Base game action error codes that apply universally across all games.
 *
 * These codes represent common game state violations that every game must handle.
 * Individual games should extend this with their own game-specific error enums.
 *
 * Severity Levels:
 * - error: Action is completely invalid and cannot be processed
 * - warning: Action has issues but might be retried
 */
enum BaseGameActionErrorCode: string
{
    /**
     * Player attempting action when it's not their turn.
     * Severity: error
     * Applicable to: Turn-based games
     */
    case NOT_PLAYER_TURN = 'not_player_turn';

    /**
     * Action attempted on a game that has already been completed.
     * Severity: error
     * Applicable to: All games
     */
    case GAME_ALREADY_COMPLETED = 'game_already_completed';

    /**
     * Action attempted on a game that is not in active status.
     * Severity: error
     * Applicable to: All games
     */
    case GAME_NOT_ACTIVE = 'game_not_active';

    /**
     * Player attempting action is not a participant in this game.
     * Severity: error
     * Applicable to: All games
     */
    case PLAYER_NOT_IN_GAME = 'player_not_in_game';

    /**
     * Turn time limit expired before action was submitted.
     * Severity: error
     * Applicable to: Timed games
     */
    case ACTION_TIMEOUT = 'action_timeout';

    /**
     * Action parameters are invalid or missing required fields.
     * Severity: error
     * Applicable to: All games
     */
    case INVALID_ACTION_PARAMETERS = 'invalid_action_parameters';

    /**
     * Action type not recognized for this game.
     * Severity: error
     * Applicable to: All games
     */
    case INVALID_ACTION_TYPE = 'invalid_action_type';

    /**
     * Action attempted during wrong game phase.
     * Severity: error
     * Applicable to: Phase-based games
     */
    case INVALID_PHASE = 'invalid_phase';

    /**
     * All players must complete their actions before proceeding.
     * Severity: warning
     * Applicable to: Simultaneous action games
     */
    case WAITING_FOR_OTHER_PLAYERS = 'waiting_for_other_players';

    /**
     * Required resource not available for this action.
     * Severity: error
     * Applicable to: Resource-based games
     */
    case RESOURCE_UNAVAILABLE = 'resource_unavailable';

    /**
     * Get the severity level for this error code.
     */
    public function severity(): string
    {
        return match ($this) {
            self::WAITING_FOR_OTHER_PLAYERS => 'warning',
            default => 'error',
        };
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::NOT_PLAYER_TURN,
            self::WAITING_FOR_OTHER_PLAYERS => true,
            default => false,
        };
    }
}
