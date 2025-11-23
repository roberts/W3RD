<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\GameEngine\Lifecycle\Conclusion\PlayerInitiatedConclusion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\AbandonGameRequest;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class GameAbandonController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected PlayerInitiatedConclusion $conclusionService
    ) {}

    /**
     * Abandon a game (no winner declared, both players penalized).
     */
    public function store(AbandonGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();

        // Process the abandon through GameEngine
        $this->conclusionService->processAbandon($game);

        return $this->messageResponse('Game abandoned', 200);
    }
}
