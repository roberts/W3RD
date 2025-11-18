<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Http\Requests\Quickplay\AcceptMatchRequest;
use App\Http\Requests\Quickplay\JoinQuickplayRequest;
use App\Http\Traits\ApiResponses;
use App\Services\GameCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

class QuickplayController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected GameCreationService $gameCreationService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Join the public matchmaking queue
     */
    public function join(JoinQuickplayRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $gameTitle = GameTitle::fromSlug($validated['game_title']);

        if (! $gameTitle) {
            return $this->errorResponse('Invalid game title');
        }

        $gameMode = $validated['game_mode'] ?? 'standard';

        // Check for cooldown
        $cooldownKey = "cooldown:quickplay:{$user->id}";
        if (Redis::exists($cooldownKey)) {
            $ttl = Redis::ttl($cooldownKey);

            return $this->errorResponse(
                'You are on a matchmaking cooldown',
                429,
                null,
                ['cooldown_remaining' => $ttl]
            );
        }

        // Add to queue (sorted set by skill level)
        $queueKey = "quickplay:{$gameTitle->value}:{$gameMode}";
        $skillLevel = $this->getUserSkillLevel($user, $gameTitle);

        Redis::zadd($queueKey, $skillLevel, (string) $user->id);

        // Store join timestamp
        Redis::hset('quickplay:timestamps', (string) $user->id, now()->timestamp);

        // Store client_id for this player (defaults to 1 = Gamer Protocol Web for AI agents)
        $clientId = (int) $request->header('X-Client-Key') ?: 1;
        Redis::hset('quickplay:clients', (string) $user->id, (string) $clientId);

        return $this->successResponse([
            'game_title' => $gameTitle->value,
            'game_mode' => $gameMode,
        ], 'Successfully joined the queue', 202);
    }

    /**
     * Leave the public matchmaking queue
     */
    public function leave(Request $request): JsonResponse
    {
        $user = $request->user();

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

        return $this->noContentResponse();
    }

    /**
     * Accept a found match
     */
    public function accept(AcceptMatchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $matchId = $validated['match_id'];
        $confirmKey = "quickplay:accept:{$matchId}";

        // Check if match still exists
        if (! Redis::exists($confirmKey)) {
            return $this->notFoundResponse('Match confirmation has expired');
        }

        // Mark this user as accepted
        Redis::hset($confirmKey, (string) $user->id, '1');

        // Check if both players have accepted
        $acceptances = Redis::hgetall($confirmKey);

        if (count($acceptances) === 2 && ! in_array('0', $acceptances)) {
            // Both players accepted - create the game
            $playerIds = array_keys($acceptances);

            $this->createGame($playerIds, $matchId);

            return $this->successResponse([
                'match_id' => $matchId,
            ], 'Match accepted! Starting game...', 202);
        }

        return $this->successResponse(null, 'Acceptance registered. Waiting for opponent...', 202);
    }

    /**
     * Get user skill level for a game title
     */
    private function getUserSkillLevel($user, GameTitle $gameTitle): int
    {
        // Get user's skill level from their title level
        $titleLevel = $user->titleLevels()
            ->where('game_title', $gameTitle->value)
            ->first();

        return $titleLevel ? $titleLevel->level : 1;
    }

    /**
     * Create a game from accepted match
     */
    private function createGame(array $playerIds, string $matchId): void
    {
        // Get game title and mode from match ID stored in Redis
        $matchKey = "quickplay:match:{$matchId}";
        $matchData = Redis::hgetall($matchKey);

        $gameTitle = GameTitle::from($matchData['game_title'] ?? 'validate-four');
        $gameMode = $matchData['game_mode'] ?? 'standard';

        // Prepare player data with each player's specific client_id
        $playerData = array_map(function ($userId) use ($matchData) {
            $clientKey = 'player_'.$userId.'_client';

            return [
                'user_id' => (int) $userId,
                'client_id' => (int) ($matchData[$clientKey] ?? 1), // Defaults to Gamer Protocol Web for AI
            ];
        }, $playerIds);

        // Create the game using the service
        $this->gameCreationService->createFromQuickplay($playerData, $gameTitle, $gameMode);

        // Clean up Redis
        Redis::del("quickplay:accept:{$matchId}");
        Redis::del($matchKey);

        // Remove players from queue and client tracking
        foreach ($playerIds as $playerId) {
            Redis::hdel('quickplay:timestamps', $playerId);
            Redis::hdel('quickplay:clients', $playerId);
        }
    }

    /**
     * Apply dodge penalty to user
     */
    public function applyDodgePenalty(int $userId): void
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
