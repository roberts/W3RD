<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Action;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameTimelineController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    /**
     * Get chronological action timeline for a game.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $game = Game::withUlid($gameUlid)->firstOrFail();

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        $actions = Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('turn_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->resourceResponse(ActionResource::collection($actions));
    }
}
