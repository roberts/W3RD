<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines the win condition and team structures.
 */
enum GameDynamic: string
{
    /**
     * 1 Winner, everyone else loses. Individual scoring.
     * Examples: Fortnite (Solos), Monopoly, Risk
     */
    case FREE_FOR_ALL = 'free_for_all';

    /**
     * Zero-sum duel.
     * Examples: Mortal Kombat, Chess, Heads-Up Poker
     */
    case ONE_VS_ONE = 'one_vs_one';

    /**
     * Fixed teams (Red vs Blue). Win/Loss is shared.
     * Examples: League of Legends, Spades, Bridge
     */
    case TEAM_BASED = 'team_based';

    /**
     * All players win or lose together against the system.
     * Examples: Pandemic, Spirit Island, Overcooked
     */
    case COOPERATIVE = 'cooperative';

    /**
     * One player (Boss) competes against a team of others.
     * Examples: Evolve, Dead by Daylight, D&D (DM vs Players)
     */
    case ONE_VS_MANY = 'one_vs_many';

    /**
     * Last player standing. Players are removed permanently during play.
     * Examples: Musical Chairs, Fall Guys, Tetris 99
     */
    case ELIMINATION = 'elimination';
}
