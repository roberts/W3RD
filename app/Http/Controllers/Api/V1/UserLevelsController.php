<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLevelsController extends Controller
{
    /**
     * Get game-specific levels for the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $levels = $user->titleLevels()
            ->get()
            ->map(function ($titleLevel) {
                return [
                    'game_title' => $titleLevel->game_title,
                    'level' => $titleLevel->level,
                    'experience_points' => $titleLevel->experience_points,
                    'games_played' => $titleLevel->games_played,
                    'games_won' => $titleLevel->games_won,
                ];
            });

        return response()->json([
            'data' => $levels,
        ]);
    }
}
