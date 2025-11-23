<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\GameEngine\Lifecycle\Conclusion\PlayerInitiatedConclusion;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameAbandonController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected PlayerInitiatedConclusion $conclusionService
    ) {}

    /**
     * Abandon a game (no winner declared, both players penalized).
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        $game = Game::withUlid($gameUlid)->firstOrFail();

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
        }

        // Process the abandon through GameEngine
        $this->conclusionService->processAbandon($game);

        return $this->messageResponse('Game abandoned', 200);
    }
}
