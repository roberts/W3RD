<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\Games\ActionResource;
use App\Http\Resources\Games\GameResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Action;
use App\Models\Games\Game;
use App\Services\Games\GameTimelineService;
use Illuminate\Http\JsonResponse;

class GameSyncController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected GameTimelineService $gameTimelineService
    ) {}

    /**
     * Get full game state for reconnection/sync after disconnection.
     * Returns complete current state plus recent action history.
     */
    public function show(ViewGameRequest $request, Game $game): JsonResponse
    {
        $game = $request->game();

        // Get last N actions for context (configurable)
        $recentActionCount = (int) $request->query('recent_actions', 10);

        $recentActions = $this->gameTimelineService->getRecentActions($game, $recentActionCount);

        $syncData = [
            'game' => GameResource::make($game),
            'recent_actions' => ActionResource::collection($recentActions),
            'synced_at' => now()->toIso8601String(),
        ];

        return $this->dataResponse($syncData);
    }
}
