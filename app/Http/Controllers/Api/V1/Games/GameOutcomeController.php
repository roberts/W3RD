<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\Games\GameOutcomeResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;

class GameOutcomeController extends Controller
{
    use ApiResponses;

    /**
     * Get final outcome of a completed game including XP, rewards, and statistics.
     */
    public function show(ViewGameRequest $request, Game $game): JsonResponse
    {
        $game = $request->game();

        // Check if game is completed
        if (! $game->isCompleted()) {
            return $this->errorResponse('Game is not yet completed', 400);
        }

        return $this->resourceResponse(GameOutcomeResource::make($game));
    }
}
