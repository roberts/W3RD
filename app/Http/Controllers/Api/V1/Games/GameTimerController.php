<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Exceptions\Game\TimerNotAvailableException;
use App\GameEngine\Timer\TimerInformationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Player;
use App\Providers\GameServiceProvider;
use Illuminate\Http\JsonResponse;

class GameTimerController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected TimerInformationService $timerService
    ) {}

    /**
     * Get current timer information for a game.
     */
    public function show(ViewGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();
        $player = $request->player();

        // Get the mode handler
        $mode = $this->handleServiceCall(
            fn () => GameServiceProvider::getMode($game),
            'Unable to load game mode handler',
            500
        );

        if ($mode instanceof JsonResponse) {
            return $mode;
        }

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

        return $this->dataResponse([
            'current_player' => [
                'ulid' => $currentPlayer?->ulid,
                'user_id' => $currentPlayer?->user_id,
                'username' => $currentPlayer?->user?->username,
            ],
            'is_your_turn' => $gameState->currentPlayerUlid === $player->ulid,
            'phase' => $gameState->phase ?? null,
            'timer' => $timerInfo,
        ]);
    }
}
