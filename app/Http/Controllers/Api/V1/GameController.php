<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
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
            /** @phpstan-ignore-next-line */
            'data' => $games->map(function (Game $game) {
                /** @phpstan-ignore-next-line */
                return [
                    'ulid' => $game->ulid,
                    'game_title' => $game->mode->game_title ?? null,
                    'status' => $game->status->value,
                    'turn_number' => $game->turn_number,
                    'winner_id' => $game->winner_id,
                    'players' => $game->players->map(function (Player $player) {
                        /** @var string|null $username */
                        $username = $player->user->username;
                        /** @var int|null $avatarId */
                        $avatarId = $player->user->avatar_id;

                        return [
                            'user_id' => $player->user_id,
                            'name' => (string) $player->user->name,
                            'username' => $username,
                            'avatar_id' => $avatarId,
                            'position' => $player->position_id,
                        ];
                    })->values(),
                    'created_at' => $game->created_at,
                    'updated_at' => $game->updated_at,
                ];
            })->values(),
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
                'status' => $game->status->value,
                'turn_number' => $game->turn_number,
                'winner_id' => $game->winner_id,
                'game_state' => $game->game_state,
                'players' => $game->players->map(function ($player) {
                    return [
                        'user_id' => $player->user_id,
                        'name' => $player->user->name,
                        'username' => $player->user->username,
                        'avatar_id' => $player->user->avatar_id,
                        'position' => $player->position_id,
                    ];
                }),
                'created_at' => $game->created_at,
                'updated_at' => $game->updated_at,
                'finished_at' => $game->finished_at,
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

    /**
     * Get move history for a specific game.
     */
    public function history(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        // Verify user is a player in this game
        $isPlayer = $game->players->contains('user_id', $user->id);

        if (! $isPlayer) {
            return response()->json([
                'message' => 'You are not authorized to view this game history.',
            ], 403);
        }

        $actions = Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('turn_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => $actions->map(function (Action $action) {
                return [
                    'id' => $action->id,
                    'turn_number' => $action->turn_number,
                    'action_type' => $action->action_type->value,
                    'action_details' => $action->action_details,
                    'player' => [
                        'user_id' => $action->player->user_id,
                        'name' => $action->player->user->name,
                        'username' => $action->player->user->username,
                        'position' => $action->player->position_id,
                    ],
                    'status' => $action->status,
                    'created_at' => $action->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Forfeit/concede a game.
     */
    public function forfeit(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = Game::where('ulid', $gameUlid)->firstOrFail();

        // Verify user is a player in this game
        /** @var Player|null $player */
        $player = $game->players()->where('user_id', $user->id)->first();

        if (! $player) {
            return response()->json([
                'message' => 'You are not authorized to forfeit this game.',
            ], 403);
        }

        // Can only forfeit active games
        if ($game->status->value !== 'active') {
            return response()->json([
                'message' => 'Can only forfeit active games.',
            ], 400);
        }

        // Determine the winner (opponent of the forfeiting player)
        /** @var Player|null $opponent */
        $opponent = $game->players()
            ->where('user_id', '!=', $user->id)
            ->first();

        if (! $opponent) {
            return response()->json([
                'message' => 'Cannot determine opponent.',
            ], 400);
        }

        // Update game status
        $game->status = \App\Enums\GameStatus::Completed;
        $game->winner_id = $opponent->user_id;
        $game->finished_at = now();
        $game->duration_seconds = now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        return response()->json([
            'data' => [
                'ulid' => $game->ulid,
                'status' => $game->status->value,
                'winner_id' => $game->winner_id,
                'finished_at' => $game->finished_at,
            ],
            'message' => 'Game forfeited successfully.',
        ]);
    }
}
