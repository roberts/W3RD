<?php

namespace App\Listeners;

use App\GameEngine\Events\GameCompleted;
use App\Models\Games\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SetAgentCooldownAfterGame
{
    /**
     * Handle the event.
     */
    public function handle(GameCompleted $event): void
    {
        $game = $event->game;

        // Check if any player is an agent
        foreach ($game->players as $player) {
            /** @var Player $player */
            if ($player->user->isAgent()) {
                $agentUserId = $player->user->id;

                // Find human opponent
                $humanPlayer = $game->players()
                    ->whereHas('user', fn ($q) => $q->whereNull('agent_id'))
                    ->first();

                if ($humanPlayer instanceof Player) {
                    // Store cooldown data
                    Redis::hmset("agent:{$agentUserId}:cooldown", [
                        'game_id' => (string) $game->id,
                        'human_user_id' => (string) $humanPlayer->user_id,
                        'game_title' => $game->title_slug->value,
                        'mode' => $game->mode,
                        'completed_at' => now()->toIso8601String(),
                    ]);

                    // Set 15-second expiration
                    Redis::expire("agent:{$agentUserId}:cooldown", 15);

                    Log::info('Agent cooldown started', [
                        'agent_id' => $agentUserId,
                        'human_user_id' => $humanPlayer->user_id,
                        'game_id' => $game->id,
                    ]);
                }
                break; // Only one agent per game in current implementation
            }
        }
    }
}
