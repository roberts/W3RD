<?php

namespace App\Actions\Quickplay;

use App\DataTransferObjects\Quickplay\QueueJoinResult;
use App\Enums\GameTitle;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Redis;

class JoinQuickplayQueueAction
{
    /**
     * Add a user to the quickplay queue.
     */
    public function execute(User $user, GameTitle $gameTitle, string $gameMode, int $clientId): QueueJoinResult
    {
        // Check for cooldown
        $cooldownKey = "cooldown:quickplay:{$user->id}";
        if (Redis::exists($cooldownKey)) {
            $ttl = Redis::ttl($cooldownKey);

            return QueueJoinResult::cooldown($ttl);
        }

        // Add to queue (sorted set by skill level)
        $queueKey = "quickplay:{$gameTitle->value}:{$gameMode}";
        $skillLevel = $this->getUserSkillLevel($user, $gameTitle);

        Redis::zadd($queueKey, $skillLevel, (string) $user->id);

        // Store join timestamp
        Redis::hset('quickplay:timestamps', (string) $user->id, now()->timestamp);

        // Store client_id for this player
        Redis::hset('quickplay:clients', (string) $user->id, (string) $clientId);

        return QueueJoinResult::success($gameTitle->value, $gameMode);
    }

    /**
     * Get user skill level for a game title
     */
    private function getUserSkillLevel(User $user, GameTitle $gameTitle): int
    {
        // Get user's skill level from their title level
        $titleLevel = $user->titleLevels()
            ->where('game_title', $gameTitle->value)
            ->first();

        return $titleLevel->level ?? 1;
    }
}
