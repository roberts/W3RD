<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\ActionResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Action;
use Illuminate\Http\JsonResponse;

class GameTimelineController extends Controller
{
    use ApiResponses;

    /**
     * Get chronological action timeline for a game.
     */
    public function show(ViewGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();

        $actions = Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('turn_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->resourceResponse(ActionResource::collection($actions));
    }
}
