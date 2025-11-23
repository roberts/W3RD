<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActionResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameTimelineController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame
    ) {}

    /**
     * Get chronological action timeline for a game.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $game = $this->findGame->execute($gameUlid);

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
