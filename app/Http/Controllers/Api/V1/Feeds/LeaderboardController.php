<?php

namespace App\Http\Controllers\Api\V1\Feeds;

use App\Enums\GameTitle;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponses;
use App\Models\Gamification\UserTitleLevel;
use App\Services\Feeds\LeaderboardService;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

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

        $leaderboard = $this->leaderboardService->getTopPlayersForTitle($title);

        return $this->dataResponse([
            'game_title' => $gameTitle,
            'entries' => $leaderboard,
        ]);
    }
}
