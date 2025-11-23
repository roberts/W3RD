<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\GameEngine\Lifecycle\Conclusion\PlayerInitiatedConclusion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ConcedeGameRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;

class GameConcedeController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected PlayerInitiatedConclusion $conclusionService
    ) {}

    /**
     * Concede a game (forfeit/resign).
     */
    public function store(ConcedeGameRequest $request, Game $game): JsonResponse
    {
        $user = $request->user();
        $game = $request->game();

        // Process the concede through GameEngine
        $this->conclusionService->processConcede($game, $user);

        return $this->messageResponse('Game conceded successfully', 200);
    }
}
