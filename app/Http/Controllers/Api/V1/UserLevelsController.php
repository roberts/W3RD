<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Gamification\UserTitleLevel;
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

        /** @var \Illuminate\Database\Eloquent\Collection<int, UserTitleLevel> $levelCollection */
        $levelCollection = $user->titleLevels()->get();

        $levels = $levelCollection->map(function (UserTitleLevel $titleLevel) {
            return [
                'game_title' => $titleLevel->title_slug,
                'level' => $titleLevel->level,
                'experience_points' => $titleLevel->xp_current,
                'last_played_at' => $titleLevel->last_played_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $levels,
        ]);
    }
}
