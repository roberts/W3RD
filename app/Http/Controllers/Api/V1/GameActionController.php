<?php

namespace App\Http\Controllers\Api\V1;

use App\Games\ValidateFour\Actions\DropDisc;
use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\Modes\StandardMode;
use App\Games\ValidateFour\Modes\PopOutMode;
use App\Games\ValidateFour\Modes\EightBySevenMode;
use App\Games\ValidateFour\Modes\NineBySixMode;
use App\Games\ValidateFour\Modes\FiveMode;
use App\Games\ValidateFour\ValidateFourGameState;
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
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

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

        // Validate request
        $validated = $request->validate([
            'action_type' => 'required|string|in:drop_disc,pop_out',
            'action_details' => 'required|array',
        ]);

        // Get the appropriate mode based on game mode
        $mode = $this->getModeForGame($game);

        // Create the game state object
        $gameState = new ValidateFourGameState($game->game_state ?? []);

        // Check if current turn has timed out
        $deadline = $mode->getActionDeadline($gameState, $game);
        if (now()->isAfter($deadline)) {
            $penalty = $mode->getTimeoutPenalty();
            
            if ($penalty === 'forfeit') {
                // Forfeit the game - other player wins
                $game->game_status = 'completed';
                $game->winner_id = $game->players()
                    ->where('ulid', '!=', $gameState->current_player_ulid)
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
        if ($gameState->current_player_ulid !== $player->ulid) {
            return response()->json([
                'error' => 'Invalid turn',
                'message' => 'It is not your turn.',
            ], 400);
        }

        // Create the action DTO
        try {
            $action = match($validated['action_type']) {
                'drop_disc' => new DropDisc($validated['action_details']),
                'pop_out' => new PopOut($validated['action_details']),
                default => throw new \Exception('Invalid action type'),
            };
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
        $winner = $mode->checkEndCondition($gameState);
        if ($winner) {
            $game->status = 'finished';
            $game->winner_id = $winner->id;
        } elseif ($gameState->is_draw) {
            $game->status = 'finished';
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

        return response()->json([
            'message' => 'Action applied successfully',
            'game' => [
                'ulid' => $game->ulid,
                'status' => $game->status,
                'game_state' => $game->game_state,
                'winner_ulid' => $gameState->winner_ulid ?? null,
                'is_draw' => $gameState->is_draw ?? false,
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
     * Get the mode instance for a game based on its mode.
     *
     * @param Game $game
     * @return object Mode instance
     */
    protected function getModeForGame(Game $game): object
    {
        // Map game modes to mode classes
        return match($game->game_mode) {
            'standard' => new StandardMode(),
            'pop_out' => new PopOutMode(),
            'eight_by_seven' => new EightBySevenMode(),
            'nine_by_six' => new NineBySixMode(),
            'five' => new FiveMode(),
            default => new StandardMode(),
        };
    }
}
