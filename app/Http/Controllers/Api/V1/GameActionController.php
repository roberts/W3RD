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

        // Create the game state object
        $gameState = new ValidateFourGameState($game->game_state ?? []);

        // Verify it's this player's turn
        if ($gameState->current_player_ulid !== $player->ulid) {
            return response()->json([
                'error' => 'Not your turn',
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

        // Get the appropriate strategy based on game mode
        // For now, defaulting to StandardMode. This should come from the game's mode field
        $strategy = new StandardMode();

        // Validate the action
        if (!$strategy->validateAction($gameState, $action)) {
            return response()->json([
                'error' => 'Invalid move',
                'message' => 'This move is not valid.',
            ], 400);
        }

        // Apply the action
        $gameState = $strategy->applyAction($gameState, $action);

        // Check for end condition
        $winner = $strategy->checkEndCondition($gameState);
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

        return response()->json([
            'message' => 'Action applied successfully',
            'game' => [
                'ulid' => $game->ulid,
                'status' => $game->status,
                'game_state' => $game->game_state,
                'winner_ulid' => $gameState->winner_ulid,
                'is_draw' => $gameState->is_draw,
            ],
        ]);
    }
}
