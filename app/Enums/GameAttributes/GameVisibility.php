<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Critical for Security.
 *
 * Determines what data the API strips from the payload before sending it to the client.
 */
enum GameVisibility: string
{
    /**
     * Everyone sees everything. No data is sanitized.
     * Examples: Chess, Checkers, Go
     */
    case PERFECT_INFORMATION = 'perfect_information';

    /**
     * Players have private data (hands/inventories) hidden from others.
     * Examples: Poker (Hole cards), Gin Rummy, Clue
     */
    case HIDDEN_INFORMATION = 'hidden_information';

    /**
     * Spatial hiding. Areas of the map are obscured until explored.
     * Examples: Starcraft 2, Civilization VI, D&D (Dungeon Crawling)
     */
    case FOG_OF_WAR = 'fog_of_war';

    /**
     * Different roles see completely different data sets.
     * Examples: Battleship, Scotland Yard, Among Us (Imposter vision)
     */
    case ASYMMETRIC_INFO = 'asymmetric_info';
}
