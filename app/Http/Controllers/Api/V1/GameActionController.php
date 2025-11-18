<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Game\HandleTimeoutAction;
use App\Actions\Game\ProcessCoordinatedActionAction;
use App\Enums\GameStatus;
use App\Events\GameActionProcessed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ProcessGameActionRequest;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Providers\GameServiceProvider;
use App\Services\GameActionRecorder;
use App\Services\Timeouts\ForfeitHandler;
use App\Services\Timeouts\NoneHandler;
use App\Services\Timeouts\PassHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameActionController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected GameActionRecorder $actionRecorder,
        protected HandleTimeoutAction $handleTimeout,
        protected ProcessCoordinatedActionAction $processCoordinatedAction
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(ProcessGameActionRequest $request, string $gameUlid): JsonResponse
    {
        // Find the game by ULID and load the mode relationship
        $game = Game::with('mode')->where('ulid', $gameUlid)->firstOrFail();

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
        if ($player instanceof JsonResponse) {
            return $player;
        }

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
        }

        // Validate request - basic validation, game-specific validation happens in the action factory
        $validated = $request->validated();

        // Dynamically get the state class for this game mode and restore state
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Check if current turn has timed out
        $timeoutResult = $this->handleTimeout->execute($game, $mode, $gameState);
        if ($timeoutResult->hasTimedOut) {
            return $timeoutResult->errorResponse;
        }

        // Verify it's this player's turn
        if ($error = $this->authorizePlayerTurn($player, $mode->getGameState()->currentPlayerUlid)) {
            return $error;
        }

        // Get the action factory for this game and create the action DTO
        $actionFactoryClass = $mode->getActionFactory();
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

        // Validate the action with rich error feedback
        $validationResult = $mode->validateAction($gameState, $action);
        if (! $validationResult->isValid) {
            // Record failed action for debugging
            $this->actionRecorder->recordFailure(
                $game,
                $player,
                $action,
                $validationResult,
                $game->turn_number ?? 1
            );

            return $this->errorResponse(
                $validationResult->message,
                400,
                $validationResult->errorCode,
                $validationResult->context
            );
        }

        // Apply the action
        $gameState = $mode->applyAction($gameState, $action);

        // Save the updated game state (may be unchanged for coordinated actions)
        $game->game_state = $gameState->toArray();
        $game->save();

        // Handle coordinated actions
        $coordinationResult = $this->processCoordinatedAction->execute($game, $action, $mode, $gameState);

        // Record the action using the service
        $actionRecord = $this->actionRecorder->recordSuccess(
            $game,
            $player,
            $action,
            $game->turn_number ?? 1,
            $coordinationResult->coordinationGroup,
            $coordinationResult->coordinationSequence
        );

        // Update game state if coordination is complete
        if ($coordinationResult->coordinationComplete && $coordinationResult->updatedGameState) {
            $gameState = $coordinationResult->updatedGameState;
            $game->game_state = $gameState->toArray();
            $game->save();
        }

        // Check for end condition using new GameOutcome
        $outcome = $mode->checkEndCondition($gameState);
        if ($outcome->isFinished) {
            $game->status = GameStatus::COMPLETED;
            $game->finish_reason = $outcome->reason;

            if ($outcome->winnerUlid) {
                /** @var Player $winner */
                $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
                $game->winner_id = $winner->id;
                $gameState = $gameState->withWinner($outcome->winnerUlid);
            }

            if ($outcome->isDraw) {
                $gameState = $gameState->withDraw();
            }

            // Store rankings and scores if provided
            if (! empty($outcome->rankings)) {
                $gameStateArray = $gameState->toArray();
                $gameStateArray['final_rankings'] = $outcome->rankings;
                $gameStateArray['final_scores'] = $outcome->scores;
                $game->game_state = $gameStateArray;
            } else {
                $game->game_state = $gameState->toArray();
            }

            $game->save();
        }

        // Increment turn number
        $game->increment('turn_number');

        // Calculate the next action deadline
        $game->refresh();
        $nextDeadline = $mode->getActionDeadline($gameState, $game);

        // Broadcast the action to all players via websocket
        broadcast(new GameActionProcessed(
            game: $game,
            actionType: $validated['action_type'],
            actionDetails: $validated['action_details'],
            playerUlid: $player->ulid,
            actionUlid: $actionRecord->ulid,
        ));

        return $this->dataResponse([
            'action' => [
                'ulid' => $actionRecord->ulid,
            ],
            'game' => [
                'ulid' => $game->ulid,
                'status' => $game->status,
                'game_state' => $game->game_state,
                'winner_ulid' => $gameState->winnerUlid,
                'is_draw' => $gameState->isDraw ?? false,
                'finish_reason' => $outcome->reason ?? null,
            ],
            'next_action_deadline' => $nextDeadline->toIso8601String(),
            'timeout' => [
                'timelimit_seconds' => $mode->getTimelimit(),
                'grace_period_seconds' => 2,
                'penalty' => $mode->getTimeoutPenalty(),
            ],
        ], 'Action applied successfully');
    }

    /**
     * Get current player options of available actions
     */
    public function options(string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

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
        if ($player instanceof JsonResponse) {
            return $player;
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
