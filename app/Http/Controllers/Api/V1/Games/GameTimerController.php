<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Exceptions\Game\TimerNotAvailableException;
use App\GameEngine\Timer\TimerInformationService;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Providers\GameServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameTimerController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame,
        protected TimerInformationService $timerService
    ) {}

    /**
     * Get current timer information for a game.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $game = $this->findGame->execute($gameUlid, ['players.user', 'mode']);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

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
        /** @var \App\Models\Game\Player|null $currentPlayer */
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
