<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines data persistence. Does the inventory/score survive the "Game Over" screen?
 */
enum GameContinuity: string
{
    /**
     * Tabula Rasa. State resets to zero when a new match starts.
     * Examples: Overwatch, Chess, Super Smash Bros
     */
    case MATCH_BASED = 'match_based';

    /**
     * Temporary persistence. State lasts for a few hours/rounds, then wipes.
     * Examples: Roguelikes (Hades), Poker Tournaments (Chips), Escape Rooms
     */
    case SESSION_BASED = 'session_based';

    /**
     * Database storage. State survives indefinitely across logins.
     * Examples: World of Warcraft, Animal Crossing, Legacy Board Games
     */
    case PERSISTENT = 'persistent';

    /**
     * (Casino) The instance never ends, it just cycles rounds.
     * Examples: Roulette Table, Slot Machine, Craps Table
     */
    case CONTINUOUS_LOOP = 'continuous_loop';
}
