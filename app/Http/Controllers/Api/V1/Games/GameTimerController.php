<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Exceptions\Game\TimerNotAvailableException;
use App\GameEngine\ModeRegistry;
use App\GameEngine\Timers\TimerInformationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\Games\GameTimerResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\HydratesGameState;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Http\JsonResponse;

class GameTimerController extends Controller
{
    use ApiResponses, HydratesGameState;

    public function __construct(
        protected TimerInformationService $timerService,
        protected ModeRegistry $modeRegistry
    ) {}

    /**
     * Get current timer information for a game.
     */
    public function show(ViewGameRequest $request, Game $game): JsonResponse
    {
        $game = $request->game();
        $player = $request->player();

        // Get the mode handler and hydrated state
        $mode = $this->modeRegistry->resolve($game);
        $gameState = $this->hydrateGameState($game);

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
