<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game\Game;
use App\Services\RematchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        protected RematchService $rematchService
    ) {}
    /**
     * List games for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $games = Game::whereHas('players', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['players.user:id,name,username,avatar_id', 'mode'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $games->map(function ($game) {
                return [
                    'ulid' => $game->ulid,
                    'game_title' => $game->mode->game_title ?? null,
                    'status' => $game->status,
                    'current_turn_user_id' => $game->current_turn_user_id,
                    'winner_user_id' => $game->winner_user_id,
                    'players' => $game->players->map(function ($player) {
                        return [
                            'user_id' => $player->user_id,
                            'name' => $player->user->name,
                            'username' => $player->user->username,
                            'avatar_id' => $player->user->avatar_id,
                            'position' => $player->position,
                        ];
                    }),
                    'created_at' => $game->created_at,
                    'updated_at' => $game->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $games->currentPage(),
                'last_page' => $games->lastPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total(),
            ],
        ]);
    }

    /**
     * Get details of a specific game.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = Game::where('ulid', $gameUlid)
            ->with(['players.user:id,name,username,avatar_id', 'mode'])
            ->firstOrFail();

        // Verify user is a player in this game
        $isPlayer = $game->players->contains('user_id', $user->id);

        if (! $isPlayer) {
            return response()->json([
                'message' => 'You are not authorized to view this game.',
            ], 403);
        }

        return response()->json([
            'data' => [
                'ulid' => $game->ulid,
                'game_title' => $game->mode->game_title ?? null,
                'status' => $game->status,
                'current_turn_user_id' => $game->current_turn_user_id,
                'winner_user_id' => $game->winner_user_id,
                'board_state' => $game->board_state,
                'players' => $game->players->map(function ($player) {
                    return [
                        'user_id' => $player->user_id,
                        'name' => $player->user->name,
                        'username' => $player->user->username,
                        'avatar_id' => $player->user->avatar_id,
                        'position' => $player->position,
                        'winner' => $player->winner,
                    ];
                }),
                'created_at' => $game->created_at,
                'updated_at' => $game->updated_at,
                'completed_at' => $game->completed_at,
            ],
        ]);
    }

    /**
     * Request a rematch for a completed game.
     */
    public function requestRematch(Request $request, string $gameUlid): JsonResponse
    {
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        try {
            $rematchRequest = $this->rematchService->createRematchRequest(
                $game,
                $request->user()
            );

            return response()->json([
                'data' => [
                    'id' => $rematchRequest->id,
                    'status' => $rematchRequest->status,
                    'expires_at' => $rematchRequest->expires_at,
                ],
                'message' => 'Rematch request sent.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
