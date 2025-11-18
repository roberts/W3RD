<?php

namespace App\Actions\Quickplay;

use App\Enums\GameTitle;
use Illuminate\Support\Facades\Redis;

class ApplyDodgePenaltyAction
{
    /**
     * Apply escalating dodge penalty to a user.
     *
     * @param  int  $userId
     * @return void
     */
    public function execute(int $userId): void
    {
        $penaltyKey = "cooldown:quickplay:{$userId}";
        $offenseKey = "quickplay:offenses:{$userId}";

        // Get offense count
        $offenses = (int) Redis::get($offenseKey) ?: 0;
        $offenses++;

        // Determine penalty duration
        $penaltyDuration = match (true) {
            $offenses === 1 => 30,      // 30 seconds
            $offenses === 2 => 120,     // 2 minutes
            default => 300,             // 5 minutes
        };

        // Set cooldown
        Redis::setex($penaltyKey, $penaltyDuration, '1');

        // Update offense count (reset after 4 hours)
        Redis::setex($offenseKey, 14400, $offenses);

        // Remove from all queues
        $gameTitles = GameTitle::cases();
        foreach ($gameTitles as $gameTitle) {
            foreach (['standard', 'blitz', 'rapid'] as $mode) {
                $queueKey = "quickplay:{$gameTitle->value}:{$mode}";
                Redis::zrem($queueKey, (string) $userId);
            }
        }
    }
}
