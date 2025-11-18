<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\ResolveClientIdAction;
use App\Actions\Quickplay\ApplyDodgePenaltyAction;
use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Actions\Quickplay\LeaveQuickplayQueueAction;
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
        protected GameCreationService $gameCreationService,
        protected ResolveClientIdAction $resolveClientId,
        protected JoinQuickplayQueueAction $joinQueue,
        protected LeaveQuickplayQueueAction $leaveQueue,
        protected ApplyDodgePenaltyAction $applyDodgePenalty
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
        $clientId = $this->resolveClientId->execute($request);

        $result = $this->joinQueue->execute($user, $gameTitle, $gameMode, $clientId);

        if (! $result->success) {
            return $this->errorResponse(
                $result->errorMessage,
                429,
                null,
                ['cooldown_remaining' => $result->cooldownRemaining]
            );
        }

        return $this->dataResponse([
            'game_title' => $result->gameTitle,
            'game_mode' => $result->gameMode,
        ], 'Successfully joined the queue', 202);
    }

    /**
     * Leave the public matchmaking queue
     */
    public function leave(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->leaveQueue->execute($user);

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

            $this->gameCreationService->createFromQuickplayMatch($playerIds, $matchId);

            return $this->dataResponse([
                'match_id' => $matchId,
            ], 'Match accepted! Starting game...', 202);
        }

        return $this->messageResponse('Acceptance registered. Waiting for opponent...', 202);
    }

    /**
     * Apply dodge penalty to user
     */
    public function applyDodgePenalty(int $userId): void
    {
        $this->applyDodgePenalty->execute($userId);
    }
}
