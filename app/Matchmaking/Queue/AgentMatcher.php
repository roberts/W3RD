<?php

declare(strict_types=1);

namespace App\Matchmaking\Queue;

use App\Agents\Scheduling\AgentSchedulingService;
use App\Enums\GameTitle;
use App\Enums\PlayerActivityState;
use App\GameEngine\Lifecycle\Creation\GameBuilder;
use App\GameEngine\Player\PlayerActivityManager;
use App\Matchmaking\Events\GameFound;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Handles matching players with AI agents as a fallback.
 */
class AgentMatcher
{
    public function __construct(
        private AgentSchedulingService $agentSchedulingService,
        private GameBuilder $gameBuilder,
        private PlayerActivityManager $activityManager,
        private RecentOpponentTracker $recentOpponentTracker
    ) {}

    /**
     * Attempt to match a player with an available agent.
     *
     * Returns true if match was successful, false if no agent available.
     */
    public function matchWithAgent(
        int $userId,
        GameTitle $gameTitle,
        string $mode,
        string $queueKey
    ): bool {
        // Find available agent FIRST before removing from queue
        $agentUser = $this->agentSchedulingService->findAvailableAgent($gameTitle->value, $mode, $userId);

        if (! $agentUser) {
            Log::info('No agent available for matchmaking, keeping user in queue', [
                'user_id' => $userId,
                'game_title' => $gameTitle->value,
                'mode' => $mode,
            ]);

            return false;
        }

        // Only remove from queue once we successfully found an agent
        Redis::zrem($queueKey, (string) $userId);
        Redis::hdel('queue:timestamps', (string) $userId);
        Redis::hdel('queue:clients', (string) $userId);

        Log::info('Matched user with AI agent', [
            'user_id' => $userId,
            'agent_id' => $agentUser->id,
            'game_title' => $gameTitle->value,
            'mode' => $mode,
        ]);

        // Create game with AI opponent
        $this->createGameWithAgent($userId, $agentUser->id, $gameTitle, $mode);

        return true;
    }

    /**
     * Create a game with an AI agent opponent.
     */
    private function createGameWithAgent(
        int $humanUserId,
        int $agentUserId,
        GameTitle $gameTitle,
        string $mode
    ): void {
        try {
            // Get client ID for human player
            $clientId = Redis::hget('queue:clients', (string) $humanUserId) ?: 1;

            // Prepare player data
            $playerData = [
                ['user_id' => $humanUserId, 'client_id' => (int) $clientId],
                ['user_id' => $agentUserId, 'client_id' => 1], // Agents use default client
            ];

            // Create the game
            $game = $this->gameBuilder->createFromQueue($playerData, $gameTitle, $mode);

            // Track recent opponents for both human and agent
            $this->recentOpponentTracker->recordMatch($humanUserId, $agentUserId);

            // Set player activity states
            $this->activityManager->setState($humanUserId, PlayerActivityState::IN_GAME);
            $this->activityManager->setState($agentUserId, PlayerActivityState::IN_GAME);

            Log::info('Game created with agent opponent', [
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
            Log::error('Failed to create game with agent', [
                'human_user_id' => $humanUserId,
                'agent_user_id' => $agentUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
