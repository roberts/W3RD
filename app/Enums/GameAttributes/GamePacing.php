<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines the time pressure and network latency requirements.
 *
 * Tells the client if it needs a websocket heartbeat or a push notification.
 */
enum GamePacing: string
{
    /**
     * Sub-second input required. Game state updates continuously (60fps).
     * Examples: Counter-Strike, Rocket League, Street Fighter
     */
    case REALTIME = 'realtime';

    /**
     * Players take turns, but stay online. Short timers (15s - 5m).
     * Examples: Hearthstone, Speed Chess (Blitz), Uno
     */
    case TURN_BASED_SYNC = 'turn_based_sync';

    /**
     * Play-by-mail style. Long timers (24h+). Players can close the app.
     * Examples: Words with Friends, Civilization (PbEM), Diplomacy
     */
    case TURN_BASED_ASYNC = 'turn_based_async';

    /**
     * Server advances state at fixed intervals regardless of input (1s - 1h ticks).
     * Examples: Clash of Clans, Travian, Cookie Clicker
     */
    case TICK_BASED = 'tick_based';
}
