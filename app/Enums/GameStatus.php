<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Game status enumeration.
 *
 * Represents the lifecycle state of a game instance.
 * Different from GamePhase which represents internal game progression.
 */
enum GameStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::COMPLETED => 'Completed',
            self::ABANDONED => 'Abandoned',
        };
    }

    /**
     * Get a description of the status.
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting for players to join or game to start',
            self::ACTIVE => 'Game is currently in progress',
            self::PAUSED => 'Game is temporarily paused',
            self::COMPLETED => 'Game has finished normally',
            self::ABANDONED => 'Game was abandoned by players',
        };
    }

    /**
     * Check if the game is in a playable state.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the game has ended.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::ABANDONED]);
    }
}
