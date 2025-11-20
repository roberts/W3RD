<?php

namespace App\Actions\Quickplay;

use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\Models\Auth\User;
use App\Services\PlayerActivityService;
use Illuminate\Support\Facades\Redis;

class LeaveQuickplayQueueAction
{
    /**
     * Remove a user from all quickplay queues.
     */
    public function execute(User $user): void
    {
        // Remove from all queues
        $gameTitles = GameTitle::cases();
        foreach ($gameTitles as $gameTitle) {
            foreach (['standard', 'blitz', 'rapid'] as $mode) {
                $queueKey = "quickplay:{$gameTitle->value}:{$mode}";
                Redis::zrem($queueKey, (string) $user->id);
            }
        }

        // Remove timestamp
        Redis::hdel('quickplay:timestamps', (string) $user->id);

        // Remove client_id
        Redis::hdel('quickplay:clients', (string) $user->id);

        // Set player activity to IDLE
        $activityService = app(PlayerActivityService::class);
        $activityService->setState($user->id, PlayerActivityState::IDLE);
    }
}
