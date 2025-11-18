<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameStatus;
use App\Events\GameActionProcessed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ProcessGameActionRequest;
use App\Http\Traits\ApiResponses;
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
    use ApiResponses;

    public function __construct(
        protected GameActionRecorder $actionRecorder
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(ProcessGameActionRequest $request, string $gameUlid): JsonResponse
    {
        // Find the game by ULID and load the mode relationship
        $game = Game::with('mode')->where('ulid', $gameUlid)->firstOrFail();

        // Get the mode handler
        try {
            $mode = GameServiceProvider::getMode($game);
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to load game mode handler.', 500, 'configuration_error');
        }

        // Verify the authenticated user is a player in this game
        /** @var Player|null $player */
        $player = $game->players()->where('user_id', Auth::id())->first();
        if (! $player) {
            return $this->forbiddenResponse('You are not a player in this game.');
        }

        // Check if game is still active
        if ($game->status !== GameStatus::ACTIVE) {
            return $this->errorResponse('This game is not active.', 400, 'invalid_game_state');
        }

        // Validate request - basic validation, game-specific validation happens in the action factory
        $validated = $request->validated();

        // Dynamically get the state class for this game mode and restore state
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Check if current turn has timed out
        $deadline = $mode->getActionDeadline($gameState, $game);
        if (now()->isAfter($deadline)) {
            $penalty = $mode->getTimeoutPenalty();

            // Get timeout handler
            $timeoutHandler = match ($penalty) {
                'forfeit' => new ForfeitHandler,
                'pass' => new PassHandler,
                'none' => new NoneHandler,
                default => new NoneHandler,
            };

            $outcome = $timeoutHandler->handleTimeout($game, $mode->getGameState(), $mode->getGameState()->currentPlayerUlid);

            if ($outcome->isFinished) {
                $game->status = GameStatus::COMPLETED;
                $game->finish_reason = $outcome->reason;

                if ($outcome->winnerUlid) {
                    /** @var Player $winner */
                    $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
                    $game->winner_id = $winner->id;
                }

                $game->save();

                return $this->errorResponse(
                    'Your turn has timed out. You have forfeited the game.',
                    408,
                    'action_timeout',
                    ['game_status' => 'completed', 'penalty' => $penalty]
                );
            }

            // Pass strategy - advance to next player
            if ($penalty === 'pass') {
                $gameState = $mode->getGameState()->withNextPlayer();
                $game->game_state = $gameState->toArray();
                $game->save();

                return $this->errorResponse(
                    'Your turn has timed out and has been passed.',
                    408,
                    'action_timeout',
                    ['penalty' => 'pass']
                );
            }
        }

        // Verify it's this player's turn
        if ($mode->getGameState()->currentPlayerUlid !== $player->ulid) {
            return $this->errorResponse('It is not your turn.', 400, 'invalid_turn');
        }

        // Get the action factory for this game and create the action DTO
        $actionFactoryClass = $mode->getActionFactory();
        try {
            $action = $actionFactoryClass::create(
                $validated['action_type'],
                $validated['action_details']
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400, 'invalid_action');
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

        // Determine coordination parameters for coordinated actions
        $coordinationGroup = null;
        $coordinationSequence = null;
        $isCoordinated = false;

        if ($action->getType() === 'pass_cards') {
            $isCoordinated = true;
            // Build coordination group for Hearts card passing
            $roundNumber = $gameState->roundNumber ?? 1;
            $coordinationGroup = "game:{$game->id}:pass:round:{$roundNumber}";

            // Get current sequence number (how many have submitted so far, before this one)
            $coordinationSequence = Action::where('game_id', $game->id)
                ->where('coordination_group', $coordinationGroup)
                ->count() + 1;
        }

        // Record the action using the service
        $actionRecord = $this->actionRecorder->recordSuccess(
            $game,
            $player,
            $action,
            $game->turn_number ?? 1,
            $coordinationGroup,
            $coordinationSequence
        );

        // Check if coordination is complete for coordinated actions
        if ($isCoordinated && $coordinationGroup) {
            $completedCount = Action::where('game_id', $game->id)
                ->where('coordination_group', $coordinationGroup)
                ->whereNull('coordination_completed_at')
                ->count();

            // For Hearts pass_cards, we need all 4 players
            $requiredCount = 4;

            if ($completedCount >= $requiredCount) {
                // All players have submitted - process the coordination
                $passActions = Action::where('game_id', $game->id)
                    ->where('coordination_group', $coordinationGroup)
                    ->whereNull('coordination_completed_at')
                    ->with('player')
                    ->get();

                // Process the coordinated action
                if (method_exists($mode, 'processPassCards')) {
                    $gameState = $mode->processPassCards($gameState, $passActions);

                    // Mark all pass actions as completed
                    Action::where('game_id', $game->id)
                        ->where('coordination_group', $coordinationGroup)
                        ->whereNull('coordination_completed_at')
                        ->update(['coordination_completed_at' => now()]);

                    // Save the updated game state after coordination
                    $game->game_state = $gameState->toArray();
                    $game->save();
                }
            }
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

        return response()->json([
            'message' => 'Action applied successfully',
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
        ]);
    }

    /**
     * Get current player options of available actions
     */
    public function options(string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        // Get the mode handler
        try {
            $mode = GameServiceProvider::getMode($game);
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to load game mode handler.', 500, 'configuration_error');
        }

        // Get the player
        /** @var Player|null $player */
        $player = $game->players()->where('user_id', Auth::id())->first();
        if (! $player) {
            return $this->forbiddenResponse('You are not a player in this game.');
        }

        // Get the current game state
        $gameState = $mode->getGameState();

        // Get available actions from mode
        $actions = $mode->getAvailableActions($gameState, $player->ulid);

        // Calculate deadline
        $deadline = $mode->getActionDeadline($gameState, $game);

        return response()->json([
            'options' => $actions,
            'is_your_turn' => $mode->getGameState()->currentPlayerUlid === $player->ulid,
            'phase' => $mode->getGameState()->phase->value ?? 'active',
            'deadline' => $deadline->toIso8601String(),
            'timelimit_seconds' => $mode->getTimelimit(),
        ]);
    }
}
