<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use Illuminate\Support\Facades\Redis;

/**
 * Tracks recent opponents to prevent immediate rematches.
 */
class RecentOpponentTracker
{
    private const RECENT_OPPONENT_LIMIT = 3;

    /**
     * Record that two players have played against each other.
     */
    public function recordMatch(int $userId1, int $userId2): void
    {
        // Update recent opponents for both players
        Redis::lpush("recent_opponents:{$userId1}", $userId2);
        Redis::ltrim("recent_opponents:{$userId1}", 0, self::RECENT_OPPONENT_LIMIT - 1);

        Redis::lpush("recent_opponents:{$userId2}", $userId1);
        Redis::ltrim("recent_opponents:{$userId2}", 0, self::RECENT_OPPONENT_LIMIT - 1);
    }

    /**
     * Get the list of recent opponents for a user.
     *
     * @return array<int>
     */
    public function getRecentOpponents(int $userId): array
    {
        return Redis::lrange("recent_opponents:{$userId}", 0, self::RECENT_OPPONENT_LIMIT - 1);
    }
}
