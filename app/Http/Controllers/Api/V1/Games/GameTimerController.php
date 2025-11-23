<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Exceptions\Game\TimerNotAvailableException;
use App\GameEngine\ModeRegistry;
use App\GameEngine\Timer\TimerInformationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\GameTimerResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Player;
use Illuminate\Http\JsonResponse;

class GameTimerController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected TimerInformationService $timerService,
        protected ModeRegistry $modeRegistry
    ) {}

    /**
     * Get current timer information for a game.
     */
    public function show(ViewGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();
        $player = $request->player();

        // Get the mode handler
        $mode = $this->modeRegistry->resolve($game);

        // Get state class and restore state
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Get timer information (throws exception if no timer)
        try {
            $timerInfo = $this->timerService->getTimerInfo($game, $mode, $gameState);
        } catch (TimerNotAvailableException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        // Find current player
        /** @var Player|null $currentPlayer */
        $currentPlayer = $game->players()
            ->where('ulid', $gameState->currentPlayerUlid)
            ->with('user:id,username')
            ->first();

        return $this->resourceResponse(GameTimerResource::make([
            'currentPlayer' => $currentPlayer,
            'isYourTurn' => $gameState->currentPlayerUlid === $player->ulid,
            'phase' => $gameState->phase ?? null,
            'timerInfo' => $timerInfo,
        ]));
    }
}
