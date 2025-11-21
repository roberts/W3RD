<?php

namespace App\Services;

use App\Enums\PlayerActivityState;
use App\Jobs\CheckAndCancelPendingProposals;
use Illuminate\Support\Facades\Redis;

/**
 * PlayerActivityService
 *
 * Tracks player activity states for presence indicators and rematch validation.
 * Uses Redis for fast lookups with automatic expiration.
 */
class PlayerActivityService
{
    private const TTL = 1800; // 30 minutes

    /**
     * Set a player's activity state.
     */
    public function setState(int $userId, PlayerActivityState $state): void
    {
        $key = "player:{$userId}:activity";
        Redis::setex($key, self::TTL, $state->value);

        Log::debug('Player activity state changed', [
            'user_id' => $userId,
            'state' => $state->value,
        ]);

        // Trigger rematch cancellation check if player is now busy
        if ($state->isBusy()) {
            dispatch(new CheckAndCancelPendingProposals($userId));
        }
    }

    /**
     * Get a player's current activity state.
     */
    public function getState(int $userId): PlayerActivityState
    {
        $key = "player:{$userId}:activity";
        $state = Redis::get($key);

        return $state ? PlayerActivityState::from($state) : PlayerActivityState::OFFLINE;
    }

    /**
     * Check if player is available for rematch.
     */
    public function isAvailableForRematch(int $userId): bool
    {
        $state = $this->getState($userId);

        return $state->isAvailableForRematch();
    }

    /**
     * Check if player is currently busy.
     */
    public function isBusy(int $userId): bool
    {
        $state = $this->getState($userId);

        return $state->isBusy();
    }

    /**
     * Refresh activity TTL for a player (keep alive).
     */
    public function refreshActivity(int $userId): void
    {
        $key = "player:{$userId}:activity";

        if (Redis::exists($key)) {
            Redis::expire($key, self::TTL);
        }
    }
}
