<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $games = Game::forUser($user->id)
            ->with(['players.user.avatar.image', 'mode'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

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
