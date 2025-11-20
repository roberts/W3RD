<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    use ApiResponses;

    /**
     * Get performance records and statistics.
     *
     * GET /v1/account/records
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Calculate global stats
        $totalGames = $user->players()->count();

        // Count wins: games where the player's ID matches the winner_id
        $wins = $user->players()->whereHas('game', function ($query) {
            $query->whereColumn('winner_id', 'players.id');
        })->count();

        // Count losses: completed games where player didn't win
        $losses = $user->players()->whereHas('game', function ($query) {
            $query->where('status', 'completed')
                ->where(function ($q) {
                    $q->whereColumn('winner_id', '!=', 'players.id')
                        ->orWhereNull('winner_id');
                });
        })->count();

        // Sum total points from the points ledger (using 'change' column)
        $totalPoints = $user->points()->sum('change');

        // TODO: Implement ELO ratings per game
        $eloRatings = [];

        // TODO: Implement global rank calculation
        $globalRank = null;

        return $this->dataResponse([
            'total_games' => $totalGames,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $totalGames - $wins - $losses,
            'win_rate' => $totalGames > 0 ? round(($wins / $totalGames) * 100, 2) : 0,
            'total_points' => $totalPoints,
            'elo_ratings' => $eloRatings,
            'global_rank' => $globalRank,
        ]);
    }
}
