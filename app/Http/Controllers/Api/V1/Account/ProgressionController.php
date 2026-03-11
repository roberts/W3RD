<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\GameEngine\Player\ProgressionManager;
use App\Http\Controllers\Controller;
use App\Http\Resources\Account\ProgressionResource;
use App\Http\Traits\ApiResponses;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressionController extends Controller
{
    use ApiResponses;

    public function __construct(
        private ProgressionManager $progressionService
    ) {}

    /**
     * Get game-specific progression (XP, levels, battle pass).
     *
     * GET /v1/account/progression
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var Collection<int, UserTitleLevel> $levelCollection */
        $levelCollection = $user->titleLevels()
            ->orderByDesc('last_played_at')
            ->get();

        $levels = $levelCollection->map(function (UserTitleLevel $titleLevel) {
            return [
                'game_title' => $titleLevel->title_slug,
                'level' => $titleLevel->level,
                'experience_points' => $titleLevel->xp_current,
                'xp_to_next_level' => $this->progressionService->calculateXpToNextLevel($titleLevel->level),
                'last_played_at' => $titleLevel->last_played_at?->toIso8601String(),
            ];
        });

        $progression = [
            'games' => $levels,
            'total_xp' => $levelCollection->sum('xp_current'),
            'average_level' => $levelCollection->avg('level'),
            // TODO: Add battle pass data
            'battle_pass' => null,
        ];

        return $this->dataResponse(ProgressionResource::make($progression)->resolve());
    }
}
