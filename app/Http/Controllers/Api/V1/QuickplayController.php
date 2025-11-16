<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Events\GameFound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class QuickplayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Join the public matchmaking queue
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_title' => 'required|string',
            'game_mode' => 'nullable|string',
        ]);

        $user = $request->user();
        $gameTitle = GameTitle::fromSlug($validated['game_title']);

        if (!$gameTitle) {
            return response()->json(['error' => 'Invalid game title'], 400);
        }

        $gameMode = $validated['game_mode'] ?? 'standard';

        // Check for cooldown
        $cooldownKey = "cooldown:quickplay:{$user->id}";
        if (Redis::exists($cooldownKey)) {
            $ttl = Redis::ttl($cooldownKey);
            return response()->json([
                'error' => 'You are on a matchmaking cooldown',
                'cooldown_remaining' => $ttl,
            ], 429);
        }

        // Add to queue (sorted set by skill level)
        $queueKey = "quickplay:{$gameTitle->value}:{$gameMode}";
        $skillLevel = $this->getUserSkillLevel($user, $gameTitle);
        
        Redis::zadd($queueKey, $skillLevel, $user->id);

        // Store join timestamp
        Redis::hset('quickplay:timestamps', $user->id, now()->timestamp);

        return response()->json([
            'message' => 'Successfully joined the queue',
            'game_title' => $gameTitle->value,
            'game_mode' => $gameMode,
        ], 202);
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
                Redis::zrem($queueKey, $user->id);
            }
        }

        // Remove timestamp
        Redis::hdel('quickplay:timestamps', $user->id);

        return response()->json(null, 204);
    }

    /**
     * Accept a found match
     */
    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'match_id' => 'required|string',
        ]);

        $user = $request->user();
        $matchId = $validated['match_id'];
        $confirmKey = "quickplay:accept:{$matchId}";

        // Check if match still exists
        if (!Redis::exists($confirmKey)) {
            return response()->json(['error' => 'Match confirmation has expired'], 404);
        }

        // Mark this user as accepted
        Redis::hset($confirmKey, $user->id, '1');

        // Check if both players have accepted
        $acceptances = Redis::hgetall($confirmKey);
        
        if (count($acceptances) === 2 && !in_array('0', $acceptances)) {
            // Both players accepted - create the game
            $playerIds = array_keys($acceptances);
            $this->createGame($playerIds, $matchId);

            return response()->json([
                'message' => 'Match accepted! Starting game...',
                'match_id' => $matchId,
            ], 202);
        }

        return response()->json([
            'message' => 'Acceptance registered. Waiting for opponent...',
        ], 202);
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
        // This will be implemented to create Game and GamePlayer records
        // For now, just clean up the match confirmation
        Redis::del("quickplay:accept:{$matchId}");
        
        // Remove players from queue
        foreach ($playerIds as $playerId) {
            Redis::hdel('quickplay:timestamps', $playerId);
        }

        // TODO: Create Game model, GamePlayer records, and broadcast GameStarted event
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
                Redis::zrem($queueKey, $userId);
            }
        }
    }
}
