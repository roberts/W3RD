<?php

namespace App\Games\Hearts\ErrorCodes;

/**
 * Error codes specific to Hearts game logic.
 *
 * These codes represent rule violations unique to Hearts gameplay.
 * Use BaseGameActionErrorCode for universal game state errors.
 */
enum HeartsActionError: string
{
    /**
     * Cannot lead with hearts until hearts have been broken.
     * Severity: error
     * Rule: Hearts cannot be led until broken (except when only hearts remain)
     */
    case CANNOT_LEAD_HEARTS = 'cannot_lead_hearts';

    /**
     * Must follow suit when able.
     * Severity: error
     * Rule: Fundamental card-following rule in Hearts
     */
    case MUST_FOLLOW_SUIT = 'must_follow_suit';

    /**
     * Cannot play Queen of Spades on first trick.
     * Severity: error
     * Rule: No points on first trick
     */
    case CANNOT_PLAY_QUEEN_ON_FIRST_TRICK = 'cannot_play_queen_on_first_trick';

    /**
     * Cannot play hearts on first trick.
     * Severity: error
     * Rule: No points on first trick
     */
    case CANNOT_PLAY_HEARTS_ON_FIRST_TRICK = 'cannot_play_hearts_on_first_trick';

    /**
     * Two of clubs must be played on first trick.
     * Severity: error
     * Rule: Player with 2♣ must lead it
     */
    case MUST_PLAY_TWO_OF_CLUBS = 'must_play_two_of_clubs';

    /**
     * Card is not in player's hand.
     * Severity: error
     */
    case CARD_NOT_IN_HAND = 'card_not_in_hand';

    /**
     * Invalid card format or specification.
     * Severity: error
     */
    case INVALID_CARD = 'invalid_card';

    /**
     * Pass direction is invalid for current round.
     * Severity: error
     */
    case INVALID_PASS_DIRECTION = 'invalid_pass_direction';

    /**
     * Wrong number of cards selected for passing.
     * Severity: error
     * Rule: Must pass exactly 3 cards
     */
    case INVALID_PASS_COUNT = 'invalid_pass_count';

    /**
     * Action attempted during wrong phase.
     * Severity: error
     */
    case WRONG_PHASE = 'wrong_phase';

    /**
     * Player has not received passed cards yet.
     * Severity: warning
     */
    case WAITING_FOR_PASSED_CARDS = 'waiting_for_passed_cards';

    /**
     * Not all players have completed passing phase.
     * Severity: warning
     */
    case WAITING_FOR_ALL_PASSES = 'waiting_for_all_passes';

    /**
     * Get the severity level for this error code.
     */
    public function severity(): string
    {
        return match ($this) {
            self::WAITING_FOR_PASSED_CARDS,
            self::WAITING_FOR_ALL_PASSES => 'warning',
            default => 'error',
        };
    }

    /**
     * Check if this error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::WAITING_FOR_PASSED_CARDS,
            self::WAITING_FOR_ALL_PASSES,
            self::CARD_NOT_IN_HAND => true,
            default => false,
        };
    }
}
