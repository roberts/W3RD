<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Game phase enumeration.
 *
 * Represents the current phase or stage of a game, allowing for complex
 * turn structures beyond simple sequential turns.
 *
 * Examples:
 * - SETUP: Pre-game actions (Hearts: passing cards, Poker: posting blinds)
 * - ACTIVE: Main gameplay phase
 * - BETTING: Poker betting rounds (preflop, flop, turn, river)
 * - RESOLUTION: End-of-round scoring or cleanup
 * - FINISHED: Game has concluded
 */
enum GamePhase: string
{
    case SETUP = 'setup';
    case ACTIVE = 'active';
    case BETTING = 'betting';
    case RESOLUTION = 'resolution';
    case FINISHED = 'finished';

    /**
     * Get a human-readable label for the phase.
     */
    public function label(): string
    {
        return match ($this) {
            self::SETUP => 'Setup',
            self::ACTIVE => 'Active',
            self::BETTING => 'Betting',
            self::RESOLUTION => 'Resolution',
            self::FINISHED => 'Finished',
        };
    }

    /**
     * Get a description of the phase.
     */
    public function description(): string
    {
        return match ($this) {
            self::SETUP => 'Pre-game setup and initialization',
            self::ACTIVE => 'Main gameplay in progress',
            self::BETTING => 'Betting or bidding round',
            self::RESOLUTION => 'Scoring and end-of-round resolution',
            self::FINISHED => 'Game has concluded',
        };
    }
}
