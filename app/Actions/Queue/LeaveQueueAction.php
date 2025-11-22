<?php

namespace App\Actions\Queue;

use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Redis;

class LeaveQueueAction
{
    /**
     * Remove a user from all matchmaking queues.
     */
    public function execute(User $user): void
    {
        // Remove from all queues
        $gameTitles = GameTitle::cases();
        foreach ($gameTitles as $gameTitle) {
            foreach (['standard', 'blitz', 'rapid'] as $mode) {
                $queueKey = "queue:{$gameTitle->value}:{$mode}";
                Redis::zrem($queueKey, (string) $user->id);
            }
        }

        // Remove timestamp
        Redis::hdel('queue:timestamps', (string) $user->id);

        // Remove client_id
        Redis::hdel('queue:clients', (string) $user->id);

        // Set player activity to IDLE
        $activityService = app(PlayerActivityManager::class);
        $activityService->setState($user->id, PlayerActivityState::IDLE);
    }
}
