<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ListGamesRequest;
use App\Http\Resources\GameResource;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Game;
use App\Services\Games\GameQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected GameQueryService $gameQueryService
    ) {}

    /**
     * List games for the authenticated user.
     */
    public function index(ListGamesRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;
        $games = $this->gameQueryService
            ->buildUserGamesQuery($user, $validated)
            ->paginate($perPage);

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
