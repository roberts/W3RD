<?php

declare(strict_types=1);

namespace App\Services\Feeds;

use App\Enums\GameTitle;
use App\Http\Resources\UserResource;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * Get top players for a specific game title.
     */
    public function getTopPlayersForTitle(GameTitle $title, int $limit = 100): Collection
    {
        return UserTitleLevel::where('game_title', $title->value)
            ->with('user.avatar.image')
            ->orderBy('level', 'desc')
            ->orderBy('experience_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(function (UserTitleLevel $titleLevel, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => UserResource::make($titleLevel->user)->resolve(),
                    'level' => $titleLevel->level,
                    'experience_points' => $titleLevel->xp_current,
                ];
            });
    }
}
