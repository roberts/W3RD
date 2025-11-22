<?php

declare(strict_types=1);

namespace App\Matchmaking\Shared;

use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;

/**
 * Service to check if players are available for matchmaking activities.
 */
class PlayerAvailabilityChecker
{
    public function __construct(
        private PlayerActivityManager $activityManager
    ) {}

    /**
     * Check if a player is available for a new game/lobby.
     */
    public function isAvailable(int $userId): bool
    {
        $state = $this->activityManager->getState($userId);

        return ! $state || $state === PlayerActivityState::IDLE;
    }

    /**
     * Check if a player is available for a rematch.
     */
    public function isAvailableForRematch(int $userId): bool
    {
        $state = $this->activityManager->getState($userId);

        if (! $state) {
            return true;
        }

        return $state->isAvailableForRematch();
    }

    /**
     * Get the current activity state of a player.
     */
    public function getState(int $userId): ?PlayerActivityState
    {
        return $this->activityManager->getState($userId);
    }

    /**
     * Set a player's activity state.
     */
    public function setState(int $userId, PlayerActivityState $state): void
    {
        $this->activityManager->setState($userId, $state);
    }
}
