<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameStatus;
use App\Events\GameActionProcessed;
use App\Http\Controllers\Controller;
use App\Models\Game\Game;
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
    public function __construct(
        protected GameActionRecorder $actionRecorder
    ) {}

    /**
     * Process a player's action in a game.
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        // Get the mode handler
        try {
            $mode = GameServiceProvider::getMode($game);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'Unable to load game mode handler.',
            ], 500);
        }

        // Verify the authenticated user is a player in this game
        $player = $game->players()->where('user_id', Auth::id())->first();
        if (! $player) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not a player in this game.',
            ], 403);
        }

        // Check if game is still active
        if ($game->status !== GameStatus::ACTIVE) {
            return response()->json([
                'error' => 'Invalid game state',
                'message' => 'This game is not active.',
            ], 400);
        }

        // Validate request - basic validation, game-specific validation happens in the action factory
        $validated = $request->validate([
            'action_type' => 'required|string',
            'action_details' => 'required|array',
        ]);

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
                    $winner = $game->players()->where('ulid', $outcome->winnerUlid)->first();
                    $game->winner_id = $winner->id;
                }

                $game->save();

                return response()->json([
                    'error' => 'Action timeout',
                    'message' => 'Your turn has timed out. You have forfeited the game.',
                    'game_status' => 'completed',
                    'penalty' => $penalty,
                ], 408);
            }

            // Pass strategy - advance to next player
            if ($penalty === 'pass') {
                $gameState = $mode->getGameState()->withNextPlayer();
                $game->game_state = $gameState->toArray();
                $game->save();

                return response()->json([
                    'error' => 'Action timeout',
                    'message' => 'Your turn has timed out and has been passed.',
                    'penalty' => 'pass',
                ], 408);
            }
        }

        // Verify it's this player's turn
        if ($mode->getGameState()->currentPlayerUlid !== $player->ulid) {
            return response()->json([
                'error' => 'Invalid turn',
                'message' => 'It is not your turn.',
            ], 400);
        }

        // Get the action factory for this game and create the action DTO
        $actionFactoryClass = $mode->getActionFactory();
        try {
            $action = $actionFactoryClass::create(
                $validated['action_type'],
                $validated['action_details']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid action',
                'message' => $e->getMessage(),
            ], 400);
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

            return response()->json([
                'error' => 'Invalid move',
                'error_code' => $validationResult->errorCode,
                'message' => $validationResult->message,
                'context' => $validationResult->context,
            ], 400);
        }

        // Apply the action
        $gameState = $mode->applyAction($gameState, $action);

        // Check for end condition using new GameOutcome
        $outcome = $mode->checkEndCondition($gameState);
        if ($outcome->isFinished) {
            $game->status = GameStatus::COMPLETED;
            $game->finish_reason = $outcome->reason;

            if ($outcome->winnerUlid) {
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
        } else {
            // Save the updated game state
            $game->game_state = $gameState->toArray();
        }

        $game->save();

        // Record the action using the service
        $this->actionRecorder->recordSuccess($game, $player, $action, $game->turn_number ?? 1);

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
        ));

        return response()->json([
            'message' => 'Action applied successfully',
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
     * Get available actions for the current player.
     */
    public function availableActions(string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        // Get the mode handler
        try {
            $mode = GameServiceProvider::getMode($game);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'Unable to load game mode handler.',
            ], 500);
        }

        // Get the player
        $player = $game->players()->where('user_id', Auth::id())->first();
        if (! $player) {
            return response()->json([
                'available_actions' => [],
                'is_your_turn' => false,
                'message' => 'You are not a player in this game.',
            ], 403);
        }

        // Get the current game state
        $gameState = $mode->getGameState();

        // Get available actions from mode
        $actions = $mode->getAvailableActions($gameState, $player->ulid);

        // Calculate deadline
        $deadline = $mode->getActionDeadline($gameState, $game);

        return response()->json([
            'available_actions' => $actions,
            'is_your_turn' => $mode->getGameState()->currentPlayerUlid === $player->ulid,
            'phase' => $mode->getGameState()->phase->value ?? 'active',
            'deadline' => $deadline->toIso8601String(),
            'timelimit_seconds' => $mode->getTimelimit(),
        ]);
    }
}
