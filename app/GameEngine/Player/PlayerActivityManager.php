<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

use App\Enums\PlayerActivityState;
use App\Jobs\CheckAndCancelPendingProposals;
use Illuminate\Support\Facades\Redis;

class PlayerActivityManager
{
    private const CACHE_TTL = 1800; // 30 minutes

    public function setState(int $userId, PlayerActivityState $state): void
    {
        Redis::setex($this->getCacheKey($userId), self::CACHE_TTL, $state->value);

        // Dispatch job to cancel pending rematch proposals if player becomes busy
        if ($state->isBusy()) {
            CheckAndCancelPendingProposals::dispatch($userId);
        }
    }

    public function getState(int $userId): ?PlayerActivityState
    {
        $stateValue = Redis::get($this->getCacheKey($userId));

        if ($stateValue === null || $stateValue === false) {
            return PlayerActivityState::OFFLINE;
        }

        return PlayerActivityState::from($stateValue);
    }

    public function clearState(int $userId): void
    {
        Redis::del($this->getCacheKey($userId));
    }

    public function refreshActivity(int $userId): void
    {
        $key = $this->getCacheKey($userId);

        if (Redis::exists($key)) {
            Redis::expire($key, self::CACHE_TTL);
        }
    }

    public function isAvailableForRematch(int $userId): bool
    {
        $state = $this->getState($userId);

        return $state && $state->isAvailableForRematch();
    }

    public function isBusy(int $userId): bool
    {
        $state = $this->getState($userId);

        return $state && $state->isBusy();
    }

    private function getCacheKey(int $userId): string
    {
        return "player:{$userId}:activity";
    }
}
