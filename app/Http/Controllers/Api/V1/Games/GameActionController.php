<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Events\GameActionProcessed;
use App\Exceptions\GameActionDeniedException;
use App\GameEngine\Actions\ActionMapper;
use App\GameEngine\GameEngine;
use App\GameEngine\ModeRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ProcessGameActionRequest;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Resources\GameActionOptionsResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected GameEngine $gameEngine,
        protected ModeRegistry $modeRegistry,
        protected ActionMapper $actionMapper
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(ProcessGameActionRequest $request, Game $game): JsonResponse
    {
        // Get game and player from FormRequest (already authorized)
        $game = $request->game();
        $player = $request->player();

        // Get the mode handler
        $mode = $this->modeRegistry->resolve($game);

        // Validate request - basic validation, game-specific validation happens in the action factory
        $validated = $request->validated();

        // Map the action using the centralized ActionMapper
        $action = $this->actionMapper->mapToAction(
            $mode,
            $validated['action_type'],
            $validated['action_details']
        );

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
    public function options(ViewGameRequest $request, Game $game): JsonResponse
    {
        $game = $request->game();
        $player = $request->player();

        // Get the mode handler
        $mode = $this->modeRegistry->resolve($game);

        // Get the current game state
        $gameState = $mode->getGameState();

        // Get available actions from mode
        $actions = $mode->getAvailableActions($gameState, $player->ulid);

        // Calculate deadline
        $deadline = $mode->getActionDeadline($gameState, $game);

        return $this->resourceResponse(GameActionOptionsResource::make([
            'actions' => $actions,
            'isYourTurn' => $gameState->currentPlayerUlid === $player->ulid,
            'phase' => $gameState->phase,
            'deadline' => $deadline,
            'timelimit' => $mode->getTimelimit(),
        ]));
    }
}
