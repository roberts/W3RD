<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Enums\GameStatus;
use App\Enums\OutcomeType;
use App\Events\GameCompleted;
use App\Events\GameStatusChanged;
use App\Exceptions\GameAccessDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Game\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameConcedeController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame
    ) {}

    /**
     * Concede a game (forfeit/resign).
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        $user = $request->user();
        $game = $this->findGame->execute($gameUlid, ['players']);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
        }

        // Determine the winner (opponent of the conceding player)
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
        $game->outcome_type = OutcomeType::FORFEIT;
        $game->completed_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        // Broadcast status change
        event(new GameStatusChanged($game));

        // Dispatch GameCompleted event for activity tracking and cooldown
        event(new GameCompleted(
            game: $game,
            winnerUlid: $opponent->ulid,
            isDraw: false
        ));

        return $this->messageResponse('Game conceded successfully', 200);
    }
}
