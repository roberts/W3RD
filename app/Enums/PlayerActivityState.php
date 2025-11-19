<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Player activity state enumeration.
 *
 * Tracks what a player is currently doing for presence indicators
 * and rematch request validation.
 */
enum PlayerActivityState: string
{
    case IDLE = 'idle';
    case IN_GAME = 'in_game';
    case IN_QUEUE = 'in_queue';
    case IN_LOBBY = 'in_lobby';
    case OFFLINE = 'offline';

    /**
     * Get a human-readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::IDLE => 'Idle',
            self::IN_GAME => 'In Game',
            self::IN_QUEUE => 'In Queue',
            self::IN_LOBBY => 'In Lobby',
            self::OFFLINE => 'Offline',
        };
    }

    /**
     * Get a description of the state.
     */
    public function description(): string
    {
        return match ($this) {
            self::IDLE => 'Player is online but not in any activity',
            self::IN_GAME => 'Player is currently in an active game',
            self::IN_QUEUE => 'Player is in matchmaking queue',
            self::IN_LOBBY => 'Player is in a lobby',
            self::OFFLINE => 'Player is offline or disconnected',
        };
    }

    /**
     * Check if player is available for rematch.
     */
    public function isAvailableForRematch(): bool
    {
        return $this === self::IDLE;
    }

    /**
     * Check if player is busy (unavailable for new activities).
     */
    public function isBusy(): bool
    {
        return in_array($this, [self::IN_GAME, self::IN_QUEUE, self::IN_LOBBY]);
    }
}
