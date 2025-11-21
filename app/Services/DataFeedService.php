<?php

namespace App\Services;

use App\Models\Game\Game;
use Illuminate\Support\Facades\Redis;

class DataFeedService
{
    /**
     * Stream live game activity via Server-Sent Events.
     */
    public function streamGameActivity(?string $gameTitle = null): void
    {
        $channel = $gameTitle ? "games.{$gameTitle}.activity" : 'games.activity';

        $this->streamRedisChannel($channel);
    }

    /**
     * Stream win announcements via Server-Sent Events.
     */
    public function streamWinAnnouncements(?string $gameTitle = null): void
    {
        $channel = $gameTitle ? "games.{$gameTitle}.wins" : 'games.wins';

        $this->streamRedisChannel($channel);
    }

    /**
     * Stream tournament updates via Server-Sent Events.
     */
    public function streamTournamentUpdates(): void
    {
        $this->streamRedisChannel('tournaments.updates');
    }

    /**
     * Stream challenge activity via Server-Sent Events.
     */
    public function streamChallengeActivity(): void
    {
        $this->streamRedisChannel('challenges.activity');
    }

    /**
     * Stream achievement unlocks via Server-Sent Events.
     */
    public function streamAchievements(?int $userId = null): void
    {
        $channel = $userId ? "achievements.user.{$userId}" : 'achievements.global';

        $this->streamRedisChannel($channel);
    }

    /**
     * Generic Redis channel streaming helper.
     */
    protected function streamRedisChannel(string $channel): void
    {
        // Keep connection alive and stream events
        $redis = Redis::connection();

        // Subscribe to channel
        $redis->subscribe([$channel], function ($message, $channelName) {
            // Send SSE event
            echo "data: {$message}\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        });
    }

    /**
     * Publish event to feed channel.
     */
    public function publishEvent(string $channel, array $data): void
    {
        $redis = Redis::connection();
        $redis->publish($channel, json_encode($data));
    }

    /**
     * Publish game completion to appropriate feeds.
     */
    public function publishGameCompletion(Game $game): void
    {
        $eventData = [
            'type' => 'game_completed',
            'game_ulid' => $game->ulid,
            'game_title' => $game->title_slug->value,
            'winner_username' => $game->winner?->user?->username,
            'outcome_type' => $game->outcome_type?->value,
            'completed_at' => $game->completed_at?->toIso8601String(),
        ];

        // Publish to general games feed
        $this->publishEvent('games.activity', $eventData);

        // Publish to game-specific feed
        $this->publishEvent("games.{$game->title_slug->value}.activity", $eventData);

        // Publish to wins feed if there's a winner
        if ($game->winner_id) {
            $this->publishEvent('games.wins', $eventData);
            $this->publishEvent("games.{$game->title_slug->value}.wins", $eventData);
        }
    }
}
