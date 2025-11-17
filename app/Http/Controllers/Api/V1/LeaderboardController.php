<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard for a specific game title.
     */
    public function show(string $gameTitle): JsonResponse
    {
        // Validate game title exists
        $validTitles = collect(config('protocol.game_titles'))->pluck('key')->toArray();

        if (! in_array($gameTitle, $validTitles)) {
            return response()->json([
                'message' => 'Game title not found.',
            ], 404);
        }

        // Get top users by level for this game title
        $leaderboard = UserTitleLevel::where('game_title', $gameTitle)
            ->with('user:id,name,username,avatar_id')
            ->orderBy('level', 'desc')
            ->orderBy('experience_points', 'desc')
            ->limit(100)
            ->get()
            ->map(function (UserTitleLevel $titleLevel, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $titleLevel->user->id,
                        'name' => $titleLevel->user->name,
                        'username' => $titleLevel->user->username,
                        'avatar_id' => $titleLevel->user->avatar_id,
                    ],
                    'level' => $titleLevel->level,
                    'experience_points' => $titleLevel->xp_current,
                ];
            });

        return response()->json([
            'data' => [
                'game_title' => $gameTitle,
                'entries' => $leaderboard,
            ],
        ]);
    }
}
