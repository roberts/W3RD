<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ForfeitGameRequest;
use App\Http\Requests\Game\RequestRematchRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\RematchRequestResource;
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
            'data' => GameResource::collection($games),
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
            'data' => GameResource::make($game),
        ]);
    }

    /**
     * Request a rematch for a completed game.
     */
    public function requestRematch(RequestRematchRequest $request, string $gameUlid): JsonResponse
    {
        $game = Game::where('ulid', $gameUlid)->with('players')->firstOrFail();

        try {
            $rematchRequest = $this->rematchService->createRematchRequest(
                $game,
                $request->user()
            );

            return response()->json([
                'data' => RematchRequestResource::make($rematchRequest),
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
            'data' => ActionResource::collection($actions),
        ]);
    }

    /**
     * Forfeit/concede a game.
     */
    public function forfeit(ForfeitGameRequest $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();
        $game = Game::where('ulid', $gameUlid)->firstOrFail();

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
