<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Events\GameActionProcessed;
use App\Exceptions\GameActionDeniedException;
use App\GameEngine\GameEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ProcessGameActionRequest;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Providers\GameServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected GameEngine $gameEngine,
        protected FindGameByUlidAction $findGame,
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(ProcessGameActionRequest $request, string $gameUlid): JsonResponse
    {
        // Find the game by ULID and load the mode relationship
        $game = $this->findGame->execute($gameUlid, ['mode']);

        // Get the mode handler
        $mode = $this->handleServiceCall(
            fn () => GameServiceProvider::getMode($game),
            'Unable to load game mode handler',
            500
        );

        if ($mode instanceof JsonResponse) {
            return $mode;
        }

        // Verify the authenticated user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
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
    public function options(string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = $this->findGame->execute($gameUlid);

        // Get the mode handler
        $mode = $this->handleServiceCall(
            fn () => GameServiceProvider::getMode($game),
            'Unable to load game mode handler',
            500
        );

        if ($mode instanceof JsonResponse) {
            return $mode;
        }

        // Get the player
        $player = $this->authorizeGamePlayer($game);

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
