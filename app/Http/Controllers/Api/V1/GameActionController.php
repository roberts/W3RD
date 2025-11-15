<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\GameActionProcessed;
use App\Http\Controllers\Controller;
use App\Models\Game\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameActionController extends Controller
{
    /**
     * Process a player's action in a game.
     *
     * @param Request $request
     * @param string $gameUlid
     * @return JsonResponse
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        // Find the game by ULID
        $game = Game::with('mode')->where('ulid', $gameUlid)->firstOrFail();

        // Verify game has a mode configured
        if (!$game->mode) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'This game does not have a valid mode configured.',
            ], 500);
        }

        // Get the mode handler
        try {
            $mode = $game->mode->getHandler();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Configuration error',
                'message' => 'Unable to load game mode handler.',
            ], 500);
        }

        // Verify the authenticated user is a player in this game
        $player = $game->players()->where('user_id', Auth::id())->first();
        if (!$player) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You are not a player in this game.',
            ], 403);
        }

        // Check if game is still active
        if ($game->status !== 'active') {
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
            
            if ($penalty === 'forfeit') {
                // Forfeit the game - other player wins
                $game->status = 'completed';
                $game->winner_id = $game->players()
                    ->where('ulid', '!=', $gameState->currentPlayerUlid)
                    ->first()
                    ->id;
                $game->save();

                return response()->json([
                    'error' => 'Action timeout',
                    'message' => 'Your turn has timed out. You have forfeited the game.',
                    'game_status' => 'completed',
                    'penalty' => 'forfeit',
                ], 408);
            } elseif ($penalty === 'pass') {
                return response()->json([
                    'error' => 'Action timeout',
                    'message' => 'Your turn has timed out and has been passed.',
                    'penalty' => 'pass',
                ], 408);
            }
        }

        // Verify it's this player's turn
        if ($gameState->currentPlayerUlid !== $player->ulid) {
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

        // Validate the action
        if (!$mode->validateAction($gameState, $action)) {
            return response()->json([
                'error' => 'Invalid move',
                'message' => 'This move is not valid.',
            ], 400);
        }

        // Apply the action
        $gameState = $mode->applyAction($gameState, $action);

        // Check for end condition
        $winnerUlid = $mode->checkEndCondition($gameState);
        if ($winnerUlid) {
            $game->status = 'completed';
            $winner = $game->players()->where('ulid', $winnerUlid)->first();
            $game->winner_id = $winner->id;
            $gameState = $gameState->withWinner($winnerUlid);
        } elseif ($gameState->isBoardFull()) {
            $game->status = 'completed';
            $gameState = $gameState->withDraw();
        }

        // Save the updated game state
        $game->game_state = $gameState->toArray();
        $game->save();

        // Record the action in the actions table
        $game->actions()->create([
            'player_id' => $player->id,
            'turn_number' => $game->turn_number ?? 1,
            'action_type' => $validated['action_type'],
            'action_details' => $validated['action_details'],
            'status' => 'success',
            'timestamp_client' => now(),
        ]);

        // Increment turn number
        $game->increment('turn_number');

        // Calculate the next action deadline
        $game->refresh(); // Refresh to get the just-created action
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
                'is_draw' => $gameState->isDraw,
            ],
            'next_action_deadline' => $nextDeadline->toIso8601String(),
            'timeout' => [
                'timelimit_seconds' => $mode->getTimelimit(),
                'grace_period_seconds' => 2,
                'penalty' => $mode->getTimeoutPenalty(),
            ],
        ]);
    }
}
