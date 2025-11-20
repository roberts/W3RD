<?php

namespace App\Jobs;

use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\Events\GameFound;
use App\Http\Controllers\Api\V1\QuickplayController;
use App\Services\Agents\AgentSchedulingService;
use App\Services\GameCreationService;
use App\Services\PlayerActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ProcessQuickplayQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const AI_FALLBACK_THRESHOLD = 20; // seconds

    private const MATCH_CONFIRMATION_TIMEOUT = 15; // seconds

    private const SKILL_RANGE = 5; // Skill level difference tolerance

    private const RECENT_OPPONENT_LIMIT = 3; // Remember last N opponents

    public function handle(): void
    {
        $gameTitles = GameTitle::cases();

        foreach ($gameTitles as $gameTitle) {
            foreach (['standard', 'blitz', 'rapid'] as $mode) {
                $this->processQueue($gameTitle, $mode);
            }
        }
    }

    private function processQueue(GameTitle $gameTitle, string $mode): void
    {
        $queueKey = "quickplay:{$gameTitle->value}:{$mode}";

        // Get all players in queue
        $players = Redis::zrange($queueKey, 0, -1, ['WITHSCORES' => true]);

        if (empty($players)) {
            return;
        }

        // Process each player
        $playersArray = [];
        for ($i = 0; $i < count($players); $i += 2) {
            $playersArray[] = [
                'user_id' => (int) $players[$i],
                'skill' => (int) $players[$i + 1],
            ];
        }

        foreach ($playersArray as $player) {
            $userId = $player['user_id'];
            $waitTime = $this->getWaitTime($userId);

            // Check for AI fallback
            if ($waitTime >= self::AI_FALLBACK_THRESHOLD) {
                $this->matchWithAI($userId, $gameTitle, $mode, $queueKey);

                continue;
            }

            // Try to find human opponent
            $opponent = $this->findOpponent($player, $playersArray, $queueKey);

            if ($opponent) {
                $this->createMatchConfirmation($userId, $opponent['user_id'], $gameTitle, $mode, $queueKey);
            }
        }
    }

    private function getWaitTime(int $userId): int
    {
        $joinTimestamp = Redis::hget('quickplay:timestamps', (string) $userId);

        if (! $joinTimestamp) {
            return 0;
        }

        return now()->timestamp - (int) $joinTimestamp;
    }

    private function findOpponent(array $player, array $allPlayers, string $queueKey): ?array
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

    private function matchWithAI(int $userId, GameTitle $gameTitle, string $mode, string $queueKey): void
    {
        // Find available agent FIRST before removing from queue
        $schedulingService = app(AgentSchedulingService::class);
        $agentUser = $schedulingService->findAvailableAgent($gameTitle->value, $mode, $userId);

        if (! $agentUser) {
            \Log::info('No agent available for matchmaking, keeping user in queue', [
                'user_id' => $userId,
                'game_title' => $gameTitle->value,
                'mode' => $mode,
            ]);

            // Keep user in queue - they'll continue waiting for human or agent
            // Agent might become available on next job run, or human might join
            return;
        }

        // Only remove from queue once we successfully found an agent
        Redis::zrem($queueKey, (string) $userId);
        Redis::hdel('quickplay:timestamps', (string) $userId);
        Redis::hdel('quickplay:clients', (string) $userId);

        \Log::info('Matched user with AI agent', [
            'user_id' => $userId,
            'agent_id' => $agentUser->id,
            'game_title' => $gameTitle->value,
            'mode' => $mode,
        ]);

        // Create game with AI opponent
        $this->createGameWithAgent($userId, $agentUser->id, $gameTitle, $mode);
    }

    private function createMatchConfirmation(int $userId1, int $userId2, GameTitle $gameTitle, string $mode, string $queueKey): void
    {
        $matchId = (string) Str::ulid();
        $confirmKey = "quickplay:accept:{$matchId}";
        $matchKey = "quickplay:match:{$matchId}";

        // Get client_ids for both players
        $client1 = Redis::hget('quickplay:clients', (string) $userId1);
        $client2 = Redis::hget('quickplay:clients', (string) $userId2);

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

        // Update recent opponents lists
        Redis::lpush("recent_opponents:{$userId1}", $userId2);
        Redis::ltrim("recent_opponents:{$userId1}", 0, self::RECENT_OPPONENT_LIMIT - 1);

        Redis::lpush("recent_opponents:{$userId2}", $userId1);
        Redis::ltrim("recent_opponents:{$userId2}", 0, self::RECENT_OPPONENT_LIMIT - 1);

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

    private function scheduleConfirmationCheck(string $matchId, int $userId1, int $userId2): void
    {
        // After timeout, check if both players accepted
        dispatch(function () use ($matchId, $userId1, $userId2) {
            $confirmKey = "quickplay:accept:{$matchId}";

            if (! Redis::exists($confirmKey)) {
                return; // Already processed
            }

            $acceptances = Redis::hgetall($confirmKey);

            // Apply penalties to non-accepters
            foreach ([$userId1, $userId2] as $userId) {
                if (! isset($acceptances[$userId]) || $acceptances[$userId] === '0') {
                    app(QuickplayController::class)->applyDodgePenalty($userId);
                }
            }

            // Clean up
            Redis::del($confirmKey);
        })->delay(now()->addSeconds(self::MATCH_CONFIRMATION_TIMEOUT + 1));
    }

    /**
     * Create a game with an AI agent opponent.
     *
     * @param  int  $humanUserId  The human player's user ID
     * @param  int  $agentUserId  The agent user's ID
     * @param  GameTitle  $gameTitle  The game title
     * @param  string  $mode  The game mode
     */
    private function createGameWithAgent(int $humanUserId, int $agentUserId, GameTitle $gameTitle, string $mode): void
    {
        try {
            // Get client ID for human player
            $clientId = Redis::hget('quickplay:clients', (string) $humanUserId) ?: 1;

            // Prepare player data
            $playerData = [
                ['user_id' => $humanUserId, 'client_id' => (int) $clientId],
                ['user_id' => $agentUserId, 'client_id' => 1], // Agents use default client
            ];

            // Create the game
            $gameCreationService = app(GameCreationService::class);
            $game = $gameCreationService->createFromQuickplay($playerData, $gameTitle, $mode);

            // Track recent opponents for both human and agent
            Redis::lpush("recent_opponents:{$humanUserId}", $agentUserId);
            Redis::ltrim("recent_opponents:{$humanUserId}", 0, self::RECENT_OPPONENT_LIMIT - 1);

            Redis::lpush("recent_opponents:{$agentUserId}", $humanUserId);
            Redis::ltrim("recent_opponents:{$agentUserId}", 0, self::RECENT_OPPONENT_LIMIT - 1);

            // Set both players' activity state to IN_GAME
            $activityService = app(PlayerActivityService::class);
            $activityService->setState($humanUserId, PlayerActivityState::IN_GAME);
            $activityService->setState($agentUserId, PlayerActivityState::IN_GAME);

            \Log::info('Game created with agent opponent', [
                'game_id' => $game->id,
                'human_user_id' => $humanUserId,
                'agent_user_id' => $agentUserId,
                'game_title' => $gameTitle->value,
                'mode' => $mode,
            ]);

            // Broadcast game found to human player
            broadcast(new GameFound($humanUserId, $game->ulid, [
                'game_title' => $gameTitle->value,
                'game_mode' => $mode,
                'opponent_id' => $agentUserId,
                'game_id' => $game->ulid,
            ]));

        } catch (\Exception $e) {
            \Log::error('Failed to create game with agent', [
                'human_user_id' => $humanUserId,
                'agent_user_id' => $agentUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
