<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Actions\Game\FindGameByUlidAction;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Http\Traits\GamePlayerAuthorization;
use App\Models\Games\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameOutcomeController extends Controller
{
    use ApiResponses, GamePlayerAuthorization;

    public function __construct(
        protected FindGameByUlidAction $findGame
    ) {}

    /**
     * Get final outcome of a completed game including XP, rewards, and statistics.
     */
    public function show(Request $request, string $gameUlid): JsonResponse
    {
        $game = $this->findGame->execute($gameUlid, ['players.user']);

        // Verify user is a player in this game
        $player = $this->authorizeGamePlayer($game);

        // Check if game is completed
        if (! $game->isCompleted()) {
            return $this->errorResponse('Game is not yet completed', 400);
        }

        /** @var Player|null $winner */
        $winner = $game->players()->where('user_id', $game->winner_id)->first();

        $outcomeData = [
            'game_ulid' => $game->ulid,
            'status' => $game->status->value,
            'outcome_type' => $game->outcome_type?->value,
            'winner' => $winner ? [
                'ulid' => $winner->ulid,
                'user_id' => $winner->user_id,
                'username' => $winner->user->username,
            ] : null,
            'is_draw' => $game->winner_id === null && $game->outcome_type?->value === 'draw',
            'completed_at' => $game->completed_at?->toIso8601String(),
            'duration_seconds' => $game->duration_seconds,
            'final_scores' => $game->final_scores ?? [],
            'xp_awarded' => $game->xp_awarded ?? [],
            'rewards' => $game->rewards ?? [],
        ];

        return $this->dataResponse($outcomeData);
    }
}
