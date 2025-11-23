<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use Illuminate\Support\Facades\Redis;

/**
 * Finds suitable human opponents from the queue.
 */
class OpponentFinder
{
    private const SKILL_RANGE = 5;

    private const RECENT_OPPONENT_LIMIT = 3;

    /**
     * Find a suitable opponent for the given player.
     *
     * @param  array{user_id: int, skill: int}  $player
     * @param  array<array{user_id: int, skill: int}>  $allPlayers
     * @return array{user_id: int, skill: int}|null
     */
    public function findOpponent(array $player, array $allPlayers, string $queueKey): ?array
    {
        $userId = $player['user_id'];
        $skill = $player['skill'];

        // Get recent opponents
        $recentOpponents = Redis::lrange("recent_opponents:{$userId}", 0, self::RECENT_OPPONENT_LIMIT - 1);

        foreach ($allPlayers as $potential) {
            $potentialId = $potential['user_id'];

            // Skip self
            if ($potentialId === $userId) {
                continue;
            }

            // Skip if already removed from queue
            if (! Redis::zscore($queueKey, $potentialId)) {
                continue;
            }

            // Skip recent opponents
            if (in_array($potentialId, $recentOpponents)) {
                continue;
            }

            // Check skill range
            if (abs($potential['skill'] - $skill) <= self::SKILL_RANGE) {
                return $potential;
            }
        }

        return null;
    }

    /**
     * Get the wait time for a player in seconds.
     */
    public function getWaitTime(int $userId): int
    {
        $joinTimestamp = Redis::hget('queue:timestamps', (string) $userId);

        if (! $joinTimestamp) {
            return 0;
        }

        return now()->timestamp - (int) $joinTimestamp;
    }
}
