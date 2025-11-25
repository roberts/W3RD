<?php

namespace App\GameTitles\Hearts\Enums;

/**
 * Error codes specific to Hearts game phases.
 *
 * Hearts has distinct phases (passing, playing) with different rules.
 * These codes help clarify which phase-specific rule was violated.
 */
enum HeartsPhaseError: string
{
    /**
     * Action only valid during passing phase.
     * Severity: error
     */
    case NOT_IN_PASSING_PHASE = 'not_in_passing_phase';

    /**
     * Action only valid during playing phase.
     * Severity: error
     */
    case NOT_IN_PLAYING_PHASE = 'not_in_playing_phase';

    /**
     * Cannot pass cards in round 4 (no-pass round).
     * Severity: error
     * Rule: Every 4th round has no passing
     */
    case NO_PASSING_THIS_ROUND = 'no_passing_this_round';

    /**
     * Passing phase already completed.
     * Severity: error
     */
    case PASSING_ALREADY_COMPLETED = 'passing_already_completed';

    /**
     * Cannot start playing until all players have passed.
     * Severity: warning
     */
    case PASSING_NOT_COMPLETE = 'passing_not_complete';

    /**
     * Round is complete, cannot play more cards.
     * Severity: error
     */
    case ROUND_ALREADY_COMPLETE = 'round_already_complete';

    /**
     * Trick is complete, must start new trick.
     * Severity: warning
     */
    case TRICK_ALREADY_COMPLETE = 'trick_already_complete';

    /**
     * Get the severity level for this error code.
     */
    public function severity(): string
    {
        return match ($this) {
            self::PASSING_NOT_COMPLETE,
            self::TRICK_ALREADY_COMPLETE => 'warning',
            default => 'error',
        };
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::PASSING_NOT_COMPLETE,
            self::TRICK_ALREADY_COMPLETE => true,
            default => false,
        };
    }
}
