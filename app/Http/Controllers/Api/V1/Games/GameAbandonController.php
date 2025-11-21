<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Enums\GameStatus;
use App\Enums\OutcomeType;
use App\Events\GameStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameAbandonController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame
    ) {}

    /**
     * Abandon a game (no winner declared, both players penalized).
     */
    public function store(Request $request, string $gameUlid): JsonResponse
    {
        $game = $this->findGame->execute($gameUlid);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is still active
        if ($error = $this->authorizeActiveGame($game)) {
            return $error;
        }

        // Mark game as abandoned (no winner)
        $game->status = GameStatus::ABANDONED;
        $game->winner_id = null;
        $game->outcome_type = OutcomeType::ABANDONED;
        $game->completed_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        // Broadcast status change
        event(new GameStatusChanged($game));

        return $this->messageResponse('Game abandoned', 200);
    }
}
