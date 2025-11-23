<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\GameResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Action;
use Illuminate\Http\JsonResponse;

class GameSyncController extends Controller
{
    use ApiResponses;

    /**
     * Get full game state for reconnection/sync after disconnection.
     * Returns complete current state plus recent action history.
     */
    public function show(ViewGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();

        // Get last N actions for context (configurable)
        $recentActionCount = (int) $request->query('recent_actions', 10);

        $recentActions = Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit($recentActionCount)
            ->get()
            ->reverse()
            ->values();

        $syncData = [
            'game' => GameResource::make($game),
            'recent_actions' => ActionResource::collection($recentActions),
            'synced_at' => now()->toIso8601String(),
        ];

        return $this->dataResponse($syncData);
    }
}
