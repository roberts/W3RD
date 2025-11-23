<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Http\Resources\GameResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Action;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSyncController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    /**
     * Get full game state for reconnection/sync after disconnection.
     * Returns complete current state plus recent action history.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $game = Game::withUlid($gameUlid, ['players.user.avatar.image', 'mode'])->firstOrFail();

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

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
