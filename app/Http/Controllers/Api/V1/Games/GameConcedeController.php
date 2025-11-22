<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\GameEngine\Lifecycle\Conclusion\PlayerInitiatedConclusion;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameConcedeController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame,
        protected PlayerInitiatedConclusion $conclusionService
    ) {}

    /**
     * Concede a game (forfeit/resign).
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();
        $game = $this->findGame->execute($gameUlid, ['players']);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
        }

        // Process the concede through GameEngine
        $this->conclusionService->processConcede($game, $user);

        return $this->messageResponse('Game conceded successfully', 200);
    }
}
