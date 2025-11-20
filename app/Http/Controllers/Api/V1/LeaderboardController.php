<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GameTitle;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponses;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    use ApiResponses;

    /**
     * Get leaderboard for a specific game title.
     */
    public function show(string $gameTitle): JsonResponse
    {
        // Validate game title exists
        $title = GameTitle::tryFrom($gameTitle);

        if (! $title) {
            throw new ResourceNotFoundException(
                'Game title not found',
                'game_title',
                $gameTitle,
                ['available_titles' => array_column(GameTitle::cases(), 'value')]
            );
        }

        // Get top users by level for this game title
        $leaderboard = UserTitleLevel::where('game_title', $gameTitle)
            ->with('user.avatar.image')
            ->orderBy('level', 'desc')
            ->orderBy('experience_points', 'desc')
            ->limit(100)
            ->get()
            ->map(function (UserTitleLevel $titleLevel, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => UserResource::make($titleLevel->user)->resolve(),
                    'level' => $titleLevel->level,
                    'experience_points' => $titleLevel->xp_current,
                ];
            });

        return $this->dataResponse([
            'game_title' => $gameTitle,
            'entries' => $leaderboard,
        ]);
    }
}
