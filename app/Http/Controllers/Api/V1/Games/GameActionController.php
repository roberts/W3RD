<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameStatus;
use App\Events\GameActionProcessed;
use App\Exceptions\GameActionDeniedException;
use App\GameEngine\Interfaces\GameRedactor;
use App\GameEngine\Lifecycle\Conclusion\ConclusionManager;
use App\GameEngine\Lifecycle\Progression\AgentCoordinator;
use App\GameEngine\Lifecycle\Progression\CoordinatedActionProcessor;
use App\GameEngine\Lifecycle\Progression\TurnManager;
use App\GameEngine\Timeline\ActionRecorder;
use App\GameEngine\Timer\TimerExpiredHandler;
use App\GameEngine\Timer\TimerScheduler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ProcessGameActionRequest;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Providers\GameServiceProvider;
use App\Services\GameResponseEnhancementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected ActionRecorder $actionRecorder,
        protected CoordinatedActionProcessor $coordinatedActionProcessor,
        protected FindGameByUlidAction $findGame,
        protected GameResponseEnhancementService $enhancementService,
        protected ConclusionManager $conclusionService,
        protected TurnManager $turnManager,
        protected TimerScheduler $timerScheduler,
        protected AgentCoordinator $agentCoordinator
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

        // Dynamically get the state class for this game mode and restore state
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Check if current turn has timed out
        $timerHandler = app(TimerExpiredHandler::class);
        $timerResult = $timerHandler->checkAndHandle($game, $mode, $gameState);
        if ($timerResult->hasExpired) {
            return $timerResult->errorResponse;
        }

        // Verify it's this player's turn
        if ($error = $this->authorizePlayerTurn($player, $gameState->currentPlayerUlid)) {
            return $error;
        }

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

            // Throw GameActionDeniedException with game-specific error code
            // Convert error code to lowercase to match enum values (INVALID_COLUMN -> invalid_column)
            $errorCode = strtolower($validationResult->errorCode);

            throw new GameActionDeniedException(
                $validationResult->message,
                $errorCode,
                $game->title_slug->value,
                $validationResult->severity ?? 'error',
                $validationResult->context ?? []
            );
        }

        // Apply the action
        $gameState = $mode->applyAction($gameState, $action);

        // Save the updated game state (may be unchanged for coordinated actions)
        $game->game_state = $gameState->toArray();
        $game->save();

        // Handle coordinated actions
        $coordinationResult = $this->coordinatedActionProcessor->process($game, $action, $mode, $gameState);

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

        // Check for end condition using the new ConclusionManager
        $this->conclusionService->determineOutcome($game);
        $game->refresh(); // Refresh to get any status changes from the service

        // Only proceed with turn advancement if the game is still active
        if ($game->status->isActive()) {
            $this->turnManager->advanceTurn($game, $mode);
            $this->timerScheduler->scheduleForNextPlayer($game, $mode);
        }

        // Calculate the next action deadline
        $game->refresh();
        $nextDeadline = $mode->getActionDeadline($gameState, $game);

        // Check if the next player is an agent and trigger their action
        $this->agentCoordinator->triggerIfAgentTurn($game, $gameState, $mode);

        // Generate rich context for the response
        $actionContext = $this->enhancementService->generateActionContext($game, $gameState, $mode, $actionRecord);

        // Broadcast the action to all players via websocket
        broadcast(new GameActionProcessed(
            game: $game,
            actionType: $validated['action_type'],
            actionDetails: $validated['action_details'],
            playerUlid: $player->ulid,
            actionUlid: $actionRecord->ulid,
            actionContext: $actionContext,
            outcomeDetails: $game->outcome_details
        ));

        return $this->dataResponse([
            'action' => [
                'ulid' => $actionRecord->ulid,
                'summary' => $actionContext['action_summary'],
            ],
            'game' => [
                'ulid' => $game->ulid,
                'status' => $game->status,
                'game_state' => app(GameRedactor::class)->redact($game, auth()->user()),
                'winner_ulid' => $gameState->winnerUlid,
                'is_draw' => $gameState->isDraw ?? false,
                'outcome_type' => $game->outcome_type,
                'outcome_details' => $game->outcome_details,
            ],
            'context' => $actionContext,
            'outcome' => $game->outcome_details,
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
