<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Events\GameActionProcessed;
use App\Exceptions\GameActionDeniedException;
use App\GameEngine\GameEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ProcessGameActionRequest;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Game;
use App\Providers\GameServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected GameEngine $gameEngine
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(ProcessGameActionRequest $request, string $gameUlid): JsonResponse
    {
        // Get game and player from FormRequest (already authorized)
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

        // Validate request - basic validation, game-specific validation happens in the action factory
        $validated = $request->validated();

        // Get the action factory for this game and create the action DTO
        $actionFactoryClass = $mode->getActionMapper();
        $action = $this->handleServiceCall(
            fn () => $actionFactoryClass::create(
                $validated['action_type'],
                $validated['action_details']
            ),
            'Invalid action',
            400
        );

        if ($action instanceof JsonResponse) {
            return $action;
        }

        // Process the action through the GameEngine (handles all orchestration)
        $result = $this->gameEngine->processPlayerAction($game, $player, $mode, $action);

        // Handle validation failure
        if (! $result->success) {
            throw new GameActionDeniedException(
                $result->validationError->message ?? 'Action failed',
                strtolower($result->validationError->errorCode ?? 'invalid_action'),
                $game->title_slug->value,
                $result->validationError->severity ?? 'error',
                $result->validationError->context ?? []
            );
        }

        // Broadcast the action to all players via websocket
        broadcast(new GameActionProcessed(
            game: $result->game,
            actionType: $validated['action_type'],
            actionDetails: $validated['action_details'],
            playerUlid: $player->ulid,
            actionUlid: $result->actionRecord->ulid,
            actionContext: $result->context,
            outcomeDetails: $result->game->outcome_details
        ));

        // Return the response array from the result
        return $this->dataResponse($result->toResponseArray(), 'Action applied successfully');
    }

    /**
     * Get current player options of available actions
     */
    public function options(ViewGameRequest $request, string $gameUlid): JsonResponse
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

        // Get the current game state
        $gameState = $mode->getGameState();

        // Get available actions from mode
        $actions = $mode->getAvailableActions($gameState, $player->ulid);

        // Calculate deadline
        $deadline = $mode->getActionDeadline($gameState, $game);

        return $this->dataResponse([
            'options' => $actions,
            'is_your_turn' => $mode->getGameState()->currentPlayerUlid === $player->ulid,
            'phase' => $mode->getGameState()->phase->value ?? 'active',
            'deadline' => $deadline->toIso8601String(),
            'timelimit_seconds' => $mode->getTimelimit(),
        ]);
    }
}
