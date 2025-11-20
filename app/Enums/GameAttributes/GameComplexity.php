<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines the target audience and cognitive load.
 *
 * Useful for filtering the Library for new users.
 */
enum GameComplexity: string
{
    /**
     * Rules explained in <1 min. High luck or simple mechanics.
     * Examples: Bingo, Tic-Tac-Toe, Candy Crush
     */
    case CASUAL = 'casual';

    /**
     * Rules take 5-10 mins. Strategy required, but mistakes aren't fatal.
     * Examples: Ticket to Ride, Catan, Standard Poker
     */
    case MIDCORE = 'midcore';

    /**
     * Rules take 30+ mins. Deep strategy, unforgiving mechanics.
     * Examples: Dwarf Fortress, Eve Online, Twilight Imperium
     */
    case HARDCORE = 'hardcore';
}
