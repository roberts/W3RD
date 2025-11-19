<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Game\FindGameByUlidAction;
use App\Enums\GameStatus;
use App\Events\GameCompleted;
use App\Exceptions\GameAccessDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\ForfeitGameRequest;
use App\Http\Requests\Game\RequestRematchRequest;
use App\Http\Resources\ActionResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\RematchRequestResource;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Services\RematchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected RematchService $rematchService,
        protected FindGameByUlidAction $findGame
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

        return $this->collectionResponse(
            $games,
            fn ($items) => GameResource::collection($items)
        );
    }

    /**
     * Get details of a specific game.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = $this->findGame->execute($gameUlid, ['players.user.avatar.image', 'mode']);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        return $this->resourceResponse(GameResource::make($game));
    }

    /**
     * Request a rematch for a completed game.
     */
    public function requestRematch(RequestRematchRequest $request, string $gameUlid): JsonResponse
    {
        $game = $this->findGame->execute($gameUlid, ['players']);

        $rematchRequest = $this->handleServiceCall(
            fn () => $this->rematchService->createRematchRequest(
                $game,
                $request->user()
            ),
            'Failed to create rematch request'
        );

        if ($rematchRequest instanceof JsonResponse) {
            return $rematchRequest;
        }

        return $this->createdResponse(
            RematchRequestResource::make($rematchRequest),
            'Rematch request sent.'
        );
    }

    /**
     * Get move history for a specific game.
     */
    public function history(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();

        $game = $this->findGame->execute($gameUlid);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        $actions = Action::where('game_id', $game->id)
            ->with('player.user:id,name,username')
            ->orderBy('turn_number', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->resourceResponse(ActionResource::collection($actions));
    }

    /**
     * Forfeit/concede a game.
     */
    public function forfeit(ForfeitGameRequest $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();
        $game = $this->findGame->execute($gameUlid);

        // Determine the winner (opponent of the forfeiting player)
        /** @var Player|null $opponent */
        $opponent = $game->players()
            ->where('user_id', '!=', $user->id)
            ->first();

        if (! $opponent) {
            throw new GameAccessDeniedException(
                'Cannot determine opponent for this game',
                $game->ulid,
                ['user_id' => $user->id]
            );
        }

        // Update game status
        $game->status = GameStatus::COMPLETED;
        $game->winner_id = $opponent->user_id;
        $game->outcome_type = \App\Enums\OutcomeType::FORFEIT;
        $game->completed_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        // Dispatch GameCompleted event for activity tracking and cooldown
        event(new GameCompleted(
            game: $game,
            winnerUlid: $opponent->ulid,
            isDraw: false
        ));

        return $this->dataResponse([
            'ulid' => $game->ulid,
            'status' => $game->status->value,
            'winner_id' => $game->winner_id,
            'completed_at' => $game->completed_at,
        ], 'Game forfeited successfully.');
    }
}
