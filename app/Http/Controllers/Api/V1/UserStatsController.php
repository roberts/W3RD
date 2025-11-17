<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStatsController extends Controller
{
    /**
     * Get global stats for the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Calculate global stats
        $totalGames = $user->players()->count();
        $wins = $user->players()->where('winner', true)->count();
        $losses = $user->players()->where('winner', false)->whereHas('game', function ($query) {
            $query->where('status', 'completed');
        })->count();
        $totalPoints = $user->points()->sum('amount');

        // TODO: Implement global rank calculation
        $globalRank = null;

        return response()->json([
            'data' => [
                'total_games' => $totalGames,
                'wins' => $wins,
                'losses' => $losses,
                'win_rate' => $totalGames > 0 ? round(($wins / $totalGames) * 100, 2) : 0,
                'total_points' => $totalPoints,
                'global_rank' => $globalRank,
            ],
        ]);
    }
}
