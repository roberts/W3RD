<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ListGamesRequest;
use App\Http\Resources\GameResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use ApiResponses;

    /**
     * List games for the authenticated user.
     */
    public function index(ListGamesRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $query = Game::forUser($user->id)
            ->with(['players.user.avatar.image', 'mode']);

        // Apply filters
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['game_title'])) {
            $query->whereHas('mode', function ($q) use ($validated) {
                $q->where('title_slug', $validated['game_title']);
            });
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to']);
        }

        if (isset($validated['opponent_username'])) {
            $query->whereHas('players.user', function ($q) use ($validated) {
                $q->where('username', $validated['opponent_username']);
            });
        }

        $perPage = $validated['per_page'] ?? 20;
        $games = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        return $this->collectionResponse(
            $games,
            fn ($items) => GameResource::collection($items)
        );
    }

    /**
     * Get details of a specific game including current state.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = Game::withUlid($gameUlid, ['players.user.avatar.image', 'mode'])->firstOrFail()->firstOrFail();

        // Verify user is a player in this game
        $isPlayer = $game->getPlayerForUser($user->id) !== null;

        if (! $isPlayer) {
            return $this->forbiddenResponse('You are not a player in this game');
        }

        return $this->resourceResponse(GameResource::make($game));
    }
}
