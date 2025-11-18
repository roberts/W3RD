<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
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
            ->with(['players.user.avatar.image', 'mode'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $games->map(function (Game $game) {
                return [
                    'ulid' => $game->ulid,
                    'game_title' => $game->mode->title_slug->value ?? null,
                    'status' => $game->status->value,
                    'turn_number' => $game->turn_number,
                    'winner_id' => $game->winner_id,
                    'players' => PlayerResource::collection($game->players)->resolve(),
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
            ->with(['players.user.avatar.image', 'mode'])
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
                'game_title' => $game->mode->title_slug->value ?? null,
                'status' => $game->status->value,
                'turn_number' => $game->turn_number,
                'winner_id' => $game->winner_id,
                'game_state' => $game->game_state,
                'players' => PlayerResource::collection($game->players)->resolve(),
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
        $game = Game::where('ulid', $gameUlid)->with('players')->firstOrFail();

        try {
            $rematchRequest = $this->rematchService->createRematchRequest(
                $game,
                $request->user()
            );

            return response()->json([
                'data' => [
                    'ulid' => $rematchRequest->ulid,
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
                    'ulid' => $action->ulid,
                    'turn_number' => $action->turn_number,
                    'action_type' => $action->action_type->value,
                    'action_details' => $action->action_details,
                    'player' => PlayerResource::make($action->player)->resolve(),
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
        $game->status = GameStatus::COMPLETED;
        $game->winner_id = $opponent->user_id;
        $game->finished_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
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
