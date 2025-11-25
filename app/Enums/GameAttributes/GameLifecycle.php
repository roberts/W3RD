<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines the "Meta" hierarchy. Who controls the start/stop button?
 */
enum GameLifecycle: string
{
    /**
     * The game is independent. Players/Host control the start.
     * Examples: Queue Match, Private Lobby, Solo Game
     */
    case STANDALONE = 'standalone';

    /**
     * The game is a "Table" inside a Tournament. The System controls the start.
     * Examples: WSOP Final Table, Esports Bracket Match, Swiss Tournament Round
     */
    case MANAGED_CHILD = 'managed_child';
}
