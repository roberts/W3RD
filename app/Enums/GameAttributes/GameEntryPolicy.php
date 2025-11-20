<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines matchmaking logic. Can I join a game that is already in progress?
 */
enum GameEntryPolicy: string
{
    /**
     * Once status is `active`, the roster is frozen.
     * Examples: Ranked Matches, Competitive Chess, FIFA
     */
    case LOCKED_ON_START = 'locked_on_start';

    /**
     * Players join/leave casually without stopping the game.
     * Examples: Cash Game Poker, Minecraft Creative, Casino Tables
     */
    case DROP_IN_DROP_OUT = 'drop_in_drop_out';

    /**
     * Entry is allowed only during specific "Lobby" or "Intermission" windows.
     * Examples: Battle Royale Lobbies, Auto-Chess, MMO Raid Queues
     */
    case WAVE_BASED = 'wave_based';

    /**
     * New players can only join to fill a slot left by a leaver (Backfill).
     * Examples: Casual Shooter (CoD), Rocket League (Casual), Ludo
     */
    case REPLACEMENT_ONLY = 'replacement_only';
}
