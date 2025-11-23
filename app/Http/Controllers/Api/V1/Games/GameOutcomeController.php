<?php

namespace App\Http\Controllers\Api\V1\Games;

use App\Http\Controllers\Controller;
use App\Http\Requests\Games\ViewGameRequest;
use App\Http\Traits\ApiResponses;
use App\Models\Games\Player;
use Illuminate\Http\JsonResponse;

class GameOutcomeController extends Controller
{
    use ApiResponses;

    /**
     * Get final outcome of a completed game including XP, rewards, and statistics.
     */
    public function show(ViewGameRequest $request, string $gameUlid): JsonResponse
    {
        $game = $request->game();

        // Check if game is completed
        if (! $game->isCompleted()) {
            return $this->errorResponse('Game is not yet completed', 400);
        }

        /** @var Player|null $winner */
        $winner = $game->winner;

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
