<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use App\Enums\GameTitle;
use App\Matchmaking\Events\GameFound;
use App\Matchmaking\Queue\Actions\ApplyDodgePenaltyAction;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Handles match confirmation flow for human-vs-human matches.
 */
class MatchConfirmationHandler
{
    private const MATCH_CONFIRMATION_TIMEOUT = 15; // seconds

    public function __construct(
        private RecentOpponentTracker $recentOpponentTracker,
        private ApplyDodgePenaltyAction $applyDodgePenalty
    ) {}

    /**
     * Create a match confirmation between two players.
     *
     * This removes them from queue, broadcasts GameFound events,
     * and schedules a penalty check if they don't accept.
     */
    public function createMatchConfirmation(
        int $userId1,
        int $userId2,
        GameTitle $gameTitle,
        string $mode,
        string $queueKey
    ): void {
        $matchId = (string) Str::ulid();
        $confirmKey = "queue:accept:{$matchId}";
        $matchKey = "queue:match:{$matchId}";

        // Get client_ids for both players
        $client1 = Redis::hget('queue:clients', (string) $userId1);
        $client2 = Redis::hget('queue:clients', (string) $userId2);

        // Create confirmation hash with TTL
        Redis::hset($confirmKey, (string) $userId1, '0');
        Redis::hset($confirmKey, (string) $userId2, '0');
        Redis::expire($confirmKey, self::MATCH_CONFIRMATION_TIMEOUT);

        // Store match metadata for game creation including each player's client_id
        Redis::hset($matchKey, 'game_title', $gameTitle->value);
        Redis::hset($matchKey, 'game_mode', $mode);
        Redis::hset($matchKey, 'player_'.$userId1.'_client', $client1 ?: '1');
        Redis::hset($matchKey, 'player_'.$userId2.'_client', $client2 ?: '1');
        Redis::expire($matchKey, self::MATCH_CONFIRMATION_TIMEOUT);

        // Remove both players from queue
        Redis::zrem($queueKey, (string) $userId1, (string) $userId2);

        // Track recent opponents
        $this->recentOpponentTracker->recordMatch($userId1, $userId2);

        // Broadcast GameFound event to both players
        $matchData = [
            'game_title' => $gameTitle->value,
            'game_mode' => $mode,
            'opponent_id' => null, // Will be filled in for each player
        ];

        broadcast(new GameFound($userId1, $matchId, array_merge($matchData, ['opponent_id' => $userId2])));
        broadcast(new GameFound($userId2, $matchId, array_merge($matchData, ['opponent_id' => $userId1])));

        // Schedule penalty check
        $this->scheduleConfirmationCheck($matchId, $userId1, $userId2);
    }

    /**
     * Schedule a job to check if both players accepted the match.
     */
    private function scheduleConfirmationCheck(string $matchId, int $userId1, int $userId2): void
    {
        // After timeout, check if both players accepted
        dispatch(function () use ($matchId, $userId1, $userId2) {
            $confirmKey = "queue:accept:{$matchId}";

            if (! Redis::exists($confirmKey)) {
                return; // Already processed
            }

            $acceptances = Redis::hgetall($confirmKey);

            // Apply penalties to non-accepters
            foreach ([$userId1, $userId2] as $userId) {
                if (! isset($acceptances[$userId]) || $acceptances[$userId] === '0') {
                    $this->applyDodgePenalty->execute($userId);
                }
            }

            // Clean up
            Redis::del($confirmKey);
        })->delay(now()->addSeconds(self::MATCH_CONFIRMATION_TIMEOUT + 1));
    }

    /**
     * Get the match confirmation timeout in seconds.
     */
    public function getConfirmationTimeout(): int
    {
        return self::MATCH_CONFIRMATION_TIMEOUT;
    }
}
